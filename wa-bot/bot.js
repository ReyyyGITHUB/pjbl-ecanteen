const fs = require("fs");
const path = require("path");
const express = require("express");
const qrcode = require("qrcode-terminal");
const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");

const PORT = Number(process.env.WA_BOT_PORT || 3055);
const BOT_TOKEN = process.env.WA_BOT_TOKEN || "ecanteen-local-demo-token";

const app = express();
app.use(express.json({ limit: "1mb" }));

let isReady = false;
let lastQrAt = null;
let lastDisconnect = null;

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

  const sellerPhone = normalizePhone(req.body.seller_phone);
  const message = String(req.body.message || "").trim();
  const orderCode = String(req.body.order_code || "").trim();
  const proofPath = String(req.body.proof_absolute_path || "").trim();

  if (!sellerPhone || !message || !orderCode) {
    res.status(422).json({ ok: false, message: "Payload order belum lengkap." });
    return;
  }

  if (!proofPath || !fs.existsSync(proofPath)) {
    res.status(422).json({ ok: false, message: "File bukti pembayaran tidak ditemukan." });
    return;
  }

  try {
    const chatId = `${sellerPhone}@c.us`;
    await client.sendMessage(chatId, message);

    const media = MessageMedia.fromFilePath(proofPath);
    await client.sendMessage(chatId, media, {
      caption: `Bukti pembayaran ${orderCode}`,
    });

    res.json({ ok: true, message: "Pesan WhatsApp terkirim." });
  } catch (error) {
    res.status(500).json({
      ok: false,
      message: error && error.message ? error.message : "Pesan WhatsApp gagal dikirim.",
    });
  }
});

client.initialize();

app.listen(PORT, "127.0.0.1", () => {
  console.log(`WhatsApp bot API berjalan di http://127.0.0.1:${PORT}`);
  console.log("Jalankan dari folder wa-bot dengan: npm install && npm start");
});
