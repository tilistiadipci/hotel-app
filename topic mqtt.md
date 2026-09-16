# Topic MQTT Hotel App

Prefix:

```text
hotel-app/hotels/{hotel_code}
```

Gunakan `hotels.code` untuk `{hotel_code}` dan `players.serial` untuk `{player_serial}`.

## Konfigurasi Broker

Setiap hotel memiliki pilihan sumber konfigurasi MQTT:

- **Default `.env`**: menggunakan `MQTT_HOST`, `MQTT_PORT`, `MQTT_CLIENT_ID`, `MQTT_AUTH_USERNAME`, `MQTT_AUTH_PASSWORD`, `MQTT_QOS`, dan `MQTT_TLS_ENABLED` dari aplikasi.
- **Khusus hotel**: menggunakan host, port, client ID, autentikasi, QoS, dan TLS yang disimpan pada konfigurasi hotel.

Hotel yang dibuat melalui registrasi memakai konfigurasi `.env` secara default. Konfigurasi khusus hanya digunakan setelah opsi **Gunakan konfigurasi khusus hotel** diaktifkan oleh superadmin.

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
  "hotel_code": "BIO-HOTEL",
  "player_serial": "BIO-TV-001",
  "timestamp": "2026-09-15T13:10:00+07:00"
}
```

Field `hotel_code` dan `player_serial` selalu dikirim agar satu handler dapat memvalidasi tujuan pesan. Untuk topic broadcast, nilai `player_serial` adalah `all`.

Nilai `type` yang didukung:

| Type | Pemicu di backend | Tindakan player |
|---|---|---|
| `checkin` | Tamu berhasil check-in | Ambil ulang status booking/tamu dan perbarui tampilan kamar |
| `checkout` | Tamu berhasil check-out | Bersihkan sesi tamu dan ambil ulang status booking |
| `tv_channels` | Akses atau master TV channel berubah | Sinkronkan daftar TV channel dari API |
| `menus` | Menu global hotel atau content khusus player berubah | Ambil ulang `GET /api/player/configuration` |
| `theme` | Tema atau detail tema berubah | Ambil konfigurasi tema terbaru |
| `configuration` | Pengaturan hotel/player berubah | Ambil konfigurasi terbaru |
| `application` | Versi/aplikasi player berubah | Jalankan pemeriksaan pembaruan aplikasi |
| `all` | Sinkronisasi penuh diminta | Sinkronkan seluruh data player |

### Event check-in

Setelah transaksi database check-in berhasil, backend melakukan publish ke player yang dipilih:

```text
hotel-app/hotels/{hotel_code}/players/{player_serial}/update
```

```json
{
  "type": "checkin",
  "action": "sync",
  "hotel_code": "BIO-HOTEL",
  "player_serial": "BIO-TV-001",
  "timestamp": "2026-09-15T13:10:00+07:00"
}
```

### Event check-out

```json
{
  "type": "checkout",
  "action": "sync",
  "hotel_code": "BIO-HOTEL",
  "player_serial": "BIO-TV-001",
  "timestamp": "2026-09-15T14:30:00+07:00"
}
```

Preview check-out tidak menerbitkan MQTT. Pesan baru dikirim setelah check-out benar-benar tersimpan.

MQTT cukup memberi tahu bahwa ada perubahan. Player kemudian mengambil data terbaru melalui API HTTP/HTTPS.

### Mengambil content/menu efektif player

```http
GET /api/player/configuration
X-Hotel-Code: BIO-HOTEL
X-Hotel-License: ******
X-Player-Token: token-player
```

Response `content.menus` sudah merupakan hasil akhir. Jika `content.uses_custom` bernilai `false`, daftar tersebut berasal dari General Settings hotel. Jika bernilai `true`, label, ikon, status, urutan, dan penempatan menu memakai konfigurasi khusus player. `icon_url` berisi URL ikon upload jika tersedia; jika `null`, player menggunakan nama ikon bawaan pada `icon`. Untuk submenu, `parent_menu_key` dan `parent_menu` menunjukkan menu utama induknya.

```json
{
  "status": true,
  "content": {
    "uses_custom": true,
    "menus": [
      {
        "key": "music",
        "name": "Music / Songs",
        "label": "Musik Kamar",
        "icon": "music",
        "icon_path": "images/player-menu-icons/player-uuid/music-file.webp",
        "icon_url": "http://localhost:3000/api/media?type=image&path=images%2Fplayer-menu-icons%2Fplayer-uuid%2Fmusic-file.webp&hotel=BIO-HOTEL",
        "placement": "main",
        "parent_menu_key": null,
        "parent_menu": null,
        "is_active": true,
        "sort_order": 3,
        "source": "player"
      },
      {
        "key": "netflix",
        "name": "Netflix",
        "label": "Netflix",
        "icon": "netflix",
        "icon_path": null,
        "icon_url": null,
        "placement": "submenu",
        "parent_menu_key": "streaming_tv",
        "parent_menu": {
          "key": "streaming_tv",
          "label": "TV Streaming"
        },
        "is_active": true,
        "sort_order": 8,
        "source": "player"
      }
    ]
  },
  "theme": {
    "id": "uuid-theme",
    "name": "Default Theme",
    "description": "Theme default hotel",
    "image_url": "http://localhost:3000/api/media?type=image&path=default%2Ftheme-1.png&hotel=BIO-HOTEL",
    "details": {
      "background_theme_color": "#ffffff"
    }
  }
}
```

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

Player sebaiknya subscribe ke topic serial miliknya dan topic `all`. Topic serial menerima event khusus seperti check-in/check-out, sedangkan topic `all` menerima perubahan bersama seperti daftar TV channel.

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
