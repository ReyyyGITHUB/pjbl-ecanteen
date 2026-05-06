const fs = require("fs");
const path = require("path");
const express = require("express");
const qrcode = require("qrcode-terminal");
const { Client, LocalAuth, MessageMedia, Poll } = require("whatsapp-web.js");

const PORT = Number(process.env.WA_BOT_PORT || 3055);
const BOT_TOKEN = process.env.WA_BOT_TOKEN || "ecanteen-local-demo-token";
const READY_ENDPOINT = process.env.ECANTEEN_READY_ENDPOINT || "http://127.0.0.1/pjbl/api/mark-order-ready.php";
const PENDING_POLLS_PATH = path.join(__dirname, "pending-polls.json");

const app = express();
app.use(express.json({ limit: "1mb" }));

let isReady = false;
let lastQrAt = null;
let lastDisconnect = null;
const pendingPolls = new Map();

const loadPendingPolls = () => {
  try {
    const raw = fs.readFileSync(PENDING_POLLS_PATH, "utf8");
    const data = JSON.parse(raw);

    for (const [messageId, meta] of Object.entries(data)) {
      pendingPolls.set(messageId, meta);
    }
  } catch (error) {
    if (error.code !== "ENOENT") {
      console.warn("Metadata poll lama gagal dibaca:", error.message);
    }
  }
};

const savePendingPolls = () => {
  fs.writeFileSync(PENDING_POLLS_PATH, JSON.stringify(Object.fromEntries(pendingPolls), null, 2));
};

const client = new Client({
  authStrategy: new LocalAuth({
    clientId: "ecanteen-seller-bot",
    dataPath: path.join(__dirname, ".wwebjs_auth"),
  }),
  puppeteer: {
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  },
});

const normalizePhone = (phone) => {
  let digits = String(phone || "").replace(/\D+/g, "");
  if (!digits) return "";
  if (digits.startsWith("0")) digits = `62${digits.slice(1)}`;
  if (digits.startsWith("8")) digits = `62${digits}`;
  return digits;
};

const getSerializedMessageId = (messageLike) => {
  if (!messageLike) return "";
  if (typeof messageLike === "string") return messageLike;
  if (messageLike._serialized) return messageLike._serialized;
  if (messageLike.id?._serialized) return messageLike.id._serialized;
  if (messageLike.parentMsgKey?._serialized) return messageLike.parentMsgKey._serialized;
  return "";
};

const extractOrderCode = (text) => {
  const match = String(text || "").match(/SNAPAN-\d{3}/i);
  return match ? match[0].toUpperCase() : "";
};

const callReadyEndpoint = async ({ orderCode, sellerPhone, readyEndpoint, action }) => {
  const response = await fetch(readyEndpoint || READY_ENDPOINT, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-WA-Bot-Token": BOT_TOKEN,
    },
    body: JSON.stringify({
      order_code: orderCode,
      seller_phone: sellerPhone,
      action,
    }),
  });
  const data = await response.json().catch(() => ({}));

  if (!response.ok || !data.ok) {
    throw new Error(data.message || "Status pesanan gagal diperbarui.");
  }

  return data;
};

const sendMessageSafe = async (chatId, content) => {
  try {
    await client.sendMessage(chatId, content);
    return true;
  } catch (error) {
    console.error(`Gagal mengirim pesan ke ${chatId}:`, error.message || error);
    return false;
  }
};

const requireToken = (req, res, next) => {
  const token = req.get("x-wa-bot-token") || "";
  if (token !== BOT_TOKEN) {
    res.status(401).json({ ok: false, message: "Token bot tidak valid." });
    return;
  }
  next();
};

client.on("qr", (qr) => {
  isReady = false;
  lastQrAt = new Date().toISOString();
  console.log("\nScan QR WhatsApp berikut untuk login bot E-Canteen:\n");
  qrcode.generate(qr, { small: true });
});

client.on("ready", () => {
  isReady = true;
  lastDisconnect = null;
  console.log("WhatsApp bot siap menerima order.");
});

client.on("authenticated", () => {
  console.log("WhatsApp bot berhasil login.");
});

client.on("auth_failure", (message) => {
  isReady = false;
  console.error("Login WhatsApp gagal:", message);
});

client.on("disconnected", (reason) => {
  isReady = false;
  lastDisconnect = `${new Date().toISOString()} - ${reason}`;
  console.error("WhatsApp bot terputus:", reason);
});

client.on("vote_update", async (vote) => {
  const selected = Array.isArray(vote.selectedOptions) ? vote.selectedOptions : [];
  const selectedNames = selected.map((option) => String(option.name || "").toLowerCase());
  const selectedReady = selectedNames.includes("siap diambil");
  const selectedReject = selectedNames.includes("tolak pesanan");
  if (!selectedReady && !selectedReject) return;

  const pollMessageId = getSerializedMessageId(vote.parentMsgKey) || getSerializedMessageId(vote.parentMessage);
  const pollMeta = pendingPolls.get(pollMessageId);

  if (!pollMeta) {
    console.warn("Vote poll diabaikan karena metadata poll tidak ditemukan. Ini biasanya vote dari poll lama sebelum bot direstart.");
    return;
  }

  const parentBody = vote.parentMessage?.body || vote.parentMessage?.pollName || "";
  const orderCode = pollMeta.orderCode || extractOrderCode(parentBody);
  const sellerPhone = pollMeta.sellerPhone;

  if (!orderCode || !sellerPhone) {
    console.warn("Vote poll diabaikan karena kode pesanan atau nomor penjual tidak ditemukan.");
    return;
  }

  try {
    const result = await callReadyEndpoint({
      orderCode,
      sellerPhone,
      readyEndpoint: pollMeta?.readyEndpoint,
      action: selectedReady ? "ready" : "reject",
    });
    const chatId = pollMeta?.chatId || `${sellerPhone}@c.us`;

    if (result.already_processed) {
      await sendMessageSafe(chatId, result.seller_message || `Pesanan ${orderCode} sudah pernah diproses.`);
      pendingPolls.delete(pollMessageId);
      savePendingPolls();
      return;
    }

    if (result.buyer_phone && result.buyer_message) {
      await sendMessageSafe(`${normalizePhone(result.buyer_phone)}@c.us`, result.buyer_message);
    }

    await sendMessageSafe(chatId, result.seller_message || `Status ${orderCode} sudah diperbarui.`);
    pendingPolls.delete(pollMessageId);
    savePendingPolls();
  } catch (error) {
    console.error("Gagal memproses vote poll:", error);
    const chatId = pollMeta?.chatId || (sellerPhone ? `${sellerPhone}@c.us` : null);
    if (chatId) {
      await sendMessageSafe(chatId, `Status ${orderCode || "pesanan"} gagal diperbarui: ${error.message}`);
    }
  }
});

app.get("/status", (req, res) => {
  res.json({
    ok: true,
    ready: isReady,
    last_qr_at: lastQrAt,
    last_disconnect: lastDisconnect,
  });
});

app.post("/send-order", requireToken, async (req, res) => {
  if (!isReady) {
    res.status(503).json({
      ok: false,
      message: "Bot WhatsApp belum siap. Scan QR atau tunggu koneksi aktif.",
    });
    return;
  }

  const recipientPhone = normalizePhone(req.body.recipient_phone || req.body.seller_phone);
  const message = String(req.body.message || "").trim();
  const orderCode = String(req.body.order_code || "").trim();
  const proofPath = String(req.body.proof_absolute_path || "").trim();
  const shouldSendStatusPoll = req.body.status_poll === true;
  const readyEndpoint = String(req.body.ready_endpoint || READY_ENDPOINT).trim();

  if (!recipientPhone || !message || !orderCode) {
    res.status(422).json({ ok: false, message: "Payload order belum lengkap." });
    return;
  }

  if (proofPath && !fs.existsSync(proofPath)) {
    res.status(422).json({ ok: false, message: "File bukti pembayaran tidak ditemukan." });
    return;
  }

  try {
    const chatId = `${recipientPhone}@c.us`;
    if (proofPath) {
      const media = MessageMedia.fromFilePath(proofPath);
      await client.sendMessage(chatId, media, {
        caption: `Bukti pembayaran ${orderCode}`,
      });
    }

    if (shouldSendStatusPoll) {
      const pollTitle = `${message}\n\nStatus pesanan ${orderCode}`;
      const poll = new Poll(pollTitle, ["Siap diambil", "Tolak pesanan"], {
        allowMultipleAnswers: false,
      });
      const pollMessage = await client.sendMessage(chatId, poll);
      const pollMessageId = getSerializedMessageId(pollMessage);
      if (pollMessageId) {
        pendingPolls.set(pollMessageId, {
          orderCode,
          sellerPhone: recipientPhone,
          chatId,
          readyEndpoint,
        });
        savePendingPolls();
      } else {
        console.warn(`Poll ${orderCode} terkirim, tetapi ID poll tidak terbaca.`);
      }
    } else {
      await client.sendMessage(chatId, message);
    }

    res.json({ ok: true, message: "Pesan WhatsApp terkirim." });
  } catch (error) {
    res.status(500).json({
      ok: false,
      message: error && error.message ? error.message : "Pesan WhatsApp gagal dikirim.",
    });
  }
});

loadPendingPolls();
client.initialize();

app.listen(PORT, "127.0.0.1", () => {
  console.log(`WhatsApp bot API berjalan di http://127.0.0.1:${PORT}`);
  console.log("Jalankan dari folder wa-bot dengan: npm install && npm start");
});
