# Topic MQTT Hotel App

Prefix:

```text
hotel-app/hotels/{hotel_code}
```

Gunakan `hotels.code` untuk `{hotel_code}` dan `players.serial` untuk `{player_serial}`.

## Daftar Topic

| Topic | Backend | Player/Hotel | Fungsi |
|---|---|---|---|
| `hotel-app/hotels/{hotel_code}/status` | Subscribe | Hotel Publish | Status hotel |
| `hotel-app/hotels/{hotel_code}/players/{player_serial}/status` | Subscribe | Player Publish | Status player |
| `hotel-app/hotels/{hotel_code}/players/{player_serial}/event` | Subscribe | Player Publish | Semua aktivitas player |
| `hotel-app/hotels/{hotel_code}/players/{player_serial}/update` | Publish | Player Subscribe | Update satu player |
| `hotel-app/hotels/{hotel_code}/players/{player_serial}/notification` | Publish | Player Subscribe | Notifikasi satu player |
| `hotel-app/hotels/{hotel_code}/players/all/update` | Publish | Semua Player Subscribe | Update semua player dalam satu hotel |
| `hotel-app/hotels/{hotel_code}/players/all/notification` | Publish | Semua Player Subscribe | Notifikasi semua player dalam satu hotel |
| `hotel-app/hotels/{hotel_code}/orders` | Subscribe | Player Publish | Semua order dari satu hotel |

## 1. Status Hotel

```text
hotel-app/hotels/{hotel_code}/status
```

```json
{
  "status": "online",
  "timestamp": "2026-09-15T13:00:00+07:00"
}
```

Nilai status cukup `online` atau `offline`.

## 2. Status Player

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/status
```

```json
{
  "status": "online",
  "room": "101",
  "ip_address": "192.168.1.10",
  "timestamp": "2026-09-15T13:01:00+07:00"
}
```

Player mengirim status saat menyala, tersambung kembali, status berubah, dan sebagai heartbeat setiap 30 detik.

Gunakan MQTT Last Will pada topic yang sama agar broker mengirim status `offline` ketika koneksi player terputus.

## 3. Semua Event Player

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/event
```

Semua aktivitas memakai satu topic. Jenis aktivitas dikirim pada field `event`.

Contoh klik menu:

```json
{
  "event": "menu_clicked",
  "data": {
    "menu_id": 10,
    "menu_name": "Room Service"
  },
  "timestamp": "2026-09-15T13:05:00+07:00"
}
```

Contoh memilih channel:

```json
{
  "event": "channel_selected",
  "data": {
    "channel_id": 5,
    "channel_name": "SCTV"
  },
  "timestamp": "2026-09-15T13:06:00+07:00"
}
```

Contoh nilai `event`:

```text
player_started
player_stopped
menu_clicked
channel_selected
video_played
application_opened
check_in
check_out
notification_received
error
```

Jika ada aktivitas baru, cukup tambahkan nilai `event`. Tidak perlu membuat topic baru.

## 4. Update Data Player

Satu player:

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/update
```

Semua player dalam satu hotel:

```text
hotel-app/hotels/{hotel_code}/players/all/update
```

```json
{
  "type": "tv_channels",
  "action": "sync",
  "timestamp": "2026-09-15T13:10:00+07:00"
}
```

Nilai `type` dapat berupa `tv_channels`, `menus`, `theme`, `configuration`, `application`, atau `all`.

MQTT cukup memberi tahu bahwa ada perubahan. Player kemudian mengambil data terbaru melalui API HTTP/HTTPS.

## 5. Alarm, Warning, dan Notifikasi

Satu player:

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/notification
```

Semua player dalam satu hotel:

```text
hotel-app/hotels/{hotel_code}/players/all/notification
```

```json
{
  "type": "warning",
  "title": "Peringatan Cuaca",
  "message": "Hujan lebat diperkirakan sampai pukul 20.00.",
  "display": "fullscreen",
  "duration": 30,
  "timestamp": "2026-09-15T13:15:00+07:00"
}
```

Nilai `type`: `alarm`, `warning`, atau `notification`.

Nilai `display`: `fullscreen`, `popup`, atau `banner`.

## 6. Order Hotel

```text
hotel-app/hotels/{hotel_code}/orders
```

```json
{
  "event": "order_created",
  "order_id": "ORD-000123",
  "player_serial": "BIO-TV-001",
  "room": "101",
  "tenant_id": 12,
  "total": 85000,
  "items": [
    {
      "menu_id": 10,
      "name": "Nasi Goreng",
      "quantity": 1,
      "price": 85000
    }
  ],
  "timestamp": "2026-09-15T13:20:00+07:00"
}
```

Nilai event order: `order_created`, `order_updated`, atau `order_cancelled`.

## Subscribe Backend

```text
hotel-app/hotels/+/status
hotel-app/hotels/+/players/+/status
hotel-app/hotels/+/players/+/event
hotel-app/hotels/+/orders
```

## Subscribe Player

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/update
hotel-app/hotels/{hotel_code}/players/{player_serial}/notification
hotel-app/hotels/{hotel_code}/players/all/update
hotel-app/hotels/{hotel_code}/players/all/notification
```

## Contoh Bio Experience Hotel

```text
hotel-app/hotels/BIO-HOTEL/status
hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/status
hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/event
hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/update
hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/notification
hotel-app/hotels/BIO-HOTEL/players/all/update
hotel-app/hotels/BIO-HOTEL/players/all/notification
hotel-app/hotels/BIO-HOTEL/orders
```

Gunakan QoS `1` untuk semua topic. Hanya topic status memakai retain `true`; topic lainnya memakai retain `false`.
