@echo off
chcp 65001 >nul
cd /d C:\laragon\www\pjbl\wa-bot

echo Cek status bot...
curl http://127.0.0.1:3055/status
echo.
echo.

echo Membuat payload...
(
echo {
echo   "recipient_phone": "6285799799857",
echo   "order_code": "SNAPAN-760",
echo   "message": "🍱 *Pesanan masuk!*\n\nHalo *raditya_rayhan* (@radityaray),\n_Pesanan kamu sudah langsung diteruskan ke penjualnya nih_ ✅\n\n🧾 *Pesanan:* 1× Ayam Geprek + 1× Ceker + 1× Gorengan\n\n🕐 *Ambil:* _Istirahat 1 (09:00 - 09:15)_\n🔖 *Kode:* *SNAPAN-760*\n💰 *Total:* *Rp 6.000*\n\n_Tunjukkan kode ini saat mengambil pesanan ya._\n*Selamat makan!* 🥳",
echo   "status_poll": false
echo }
) > payload-wa.json

echo Kirim pesan WA...
curl -X POST http://127.0.0.1:3055/send-order -H "Content-Type: application/json" -H "X-WA-Bot-Token: ecanteen-local-demo-token" --data-binary @payload-wa.json

echo.
echo Selesai.
pause
