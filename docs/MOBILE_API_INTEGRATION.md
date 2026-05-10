# Mobile app API integration guide (Dreamers Arena / Bookven)

This document helps **frontend (mobile) developers** wire the app screens to the Laravel API. It maps each major UI flow to concrete endpoints, payloads, and display rules. Screenshot references describe the design you are implementing; attach your exported PNGs next to this file if you want a visual appendix (`docs/screenshots/`).

**Base URL:** `{APP_URL}/api/v1` (example: `https://api.example.com/api/v1`).

---

## 1. Conventions

### 1.1 JSON envelope

Successful and error responses share this shape:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "errors": {}
}
```

- On validation errors, `success` is `false`, HTTP status is typically **422**, and `errors` contains field keys.
- Lists are usually returned **inside** `data` as an array, or as a named object (e.g. `data.courts`).

### 1.2 Authentication (Bearer token)

Use **Laravel Sanctum** personal access tokens.

1. `POST /login` with `email`, `password`, optional `device_name` (e.g. `pixel-8`).
2. Read `data.token` from the response.
3. Send header on every protected request:  
   `Authorization: Bearer {token}`

**Logout:** `POST /logout` (invalidates the current token).

**Profile (My Account):** `GET /user` returns the same user shape as login (`data` is a single `UserResource`).

**Password reset (Forgot password):**

- `POST /password/forgot` — body: `email`
- `POST /password/reset` — Laravel’s standard reset fields (`email`, `password`, `password_confirmation`, `token`)

### 1.3 Roles and branch scoping

- Staff roles (`manager`, `admin`, `super_admin`) can see **branch-scoped** lists when `all=1` is used on some listing endpoints (see bookings).
- **Managers** should see **today’s timeline** for bookings in their branches (see `GET /screens/home` and `GET /bookings/today`).
- **Players** (`user`) only see their own bookings unless your product changes that rule.

User payload includes:

- `role` — machine value: `user` | `manager` | `admin` | `super_admin`
- `role_label` — human text (e.g. `Branch Manager` for `manager`)
- `profile_image_url` — reserved for future use (currently `null`)

### 1.4 Two different “type” concepts (important)

| Field | Meaning | Example |
|--------|---------|--------|
| `court.type` | Physical court classification | `Indoor` / `Outdoor` |
| `court.indoor_facility_kind` | UI **“Indoor Type”** dropdown (facility category) | `court` / `net` |

The mobile **“Select Indoor Type”** (Court vs Net) maps to **`indoor_facility_kind`**, not to `court.type`.

---

## 2. Screen map → endpoints

### 2.1 Splash & version

| UI | Endpoint | Notes |
|----|-----------|--------|
| Splash / “Dreamers Arena 3.0”, version label | `GET /app/config` | **Public.** Returns `app_name`, `api_version`, optional `min_supported_mobile_version`, `currency_code`, `currency_symbol` (defaults are PKR-oriented; adjust in `AppConfigController` if needed). |

Use this to show build/version and optional “force update” if you add `min_supported_mobile_version` later.

---

### 2.2 Login

| UI | Endpoint | Body / notes |
|----|----------|----------------|
| Email, password, Remember me | `POST /login` | `email`, `password`, optional `device_name`. “Remember me” is **client-only** (persist token securely). |

---

### 2.3 Home (greeting, quick actions, today’s timeline)

| UI | Endpoint | Notes |
|----|----------|--------|
| “Hi, {name}”, role, avatar | `GET /screens/home` → `data.user` | Full `UserResource` (name, role, role_label, branches…). |
| Today’s booking timeline rows | `GET /screens/home` → `data.todays_booking_timeline` | Array of bookings for **today** (scoped like manager vs player). |
| Same timeline (alternative) | `GET /bookings/today` | Dedicated list if you prefer not to use the screen bundle. |
| New Booking / Booking History cards | Navigation only | **New booking** → start flow in §3. **History** → `GET /users/{id}/history` (bookings array) or filtered `GET /bookings` (see §4). |

**Timeline row display:** Use `customer_name` (guest) when present; otherwise fall back to the booking owner’s name from `user` if you include it. Combine with court name, e.g. `{customer_name ?? user.name} — {court.name}`. Format times from `slot.start_time` / `slot.end_time` + `date` (see §5).

---

### 2.4 Slots / New booking — indoor type & date

| UI | Endpoint | Notes |
|----|----------|--------|
| Dropdown “Court / Net” | `GET /indoor-types` | Returns `[{ id, key, label, icon_key }]`. |
| Pick branch (if you show multiple) | `GET /branches` | User’s accessible branches. |
| **All courts + slots for a day** (grid under Court A / Court B) | `GET /branches/{branch}/slot-board?date=YYYY-MM-DD&indoor_facility_kind=court` | `indoor_facility_kind` optional; omit to load all kinds. Response: `courts[]` each with `court` + `slots[]`. |
| Single court | `GET /courts/{court}/slots?date=YYYY-MM-DD` | Same slot list for one court. |

**Branches preview on home:** `GET /screens/home` → `data.branches_preview`.

---

### 2.5 Booking confirmation modal (customer name, phone, amounts)

After the user picks **court + one or more slots + date**, create the booking:

`POST /bookings`

| Field | Type | Required | Notes |
|-------|------|----------|--------|
| `court_id` | int | ✓ | Must belong to an accessible branch. |
| `slot_id` | int | ✓* | Single slot. Use this **or** `slot_ids`, not both. |
| `slot_ids` | int[] | ✓* | Multiple slots on the same court and date (max 50, distinct ids). Same day-of-week rules as `slot_id`. |
| `date` | string | ✓ | `YYYY-MM-D`, not in the past. |
| `advance_amount` | number | Optional | Advance / deposit for the **combined** booking. Split across created rows by each slot’s share of the total. Capped to total server-side. |
| `customer_name` | string | Optional | **Guest / customer name** on the receipt. |
| `customer_phone` | string | Optional | **Contact number** on the receipt. |
| `total_amount` | number | Optional | **Staff only** (`manager` / `admin` / `super_admin`). Overrides calculated **combined** price; split across rows by each slot’s default price share. |

\* Exactly one of `slot_id` or `slot_ids` is required.

**Multi-slot response (201):** `data.bookings` is an array of booking objects (same shape as a single booking). Each row has its own `id` — use `POST /bookings/{id}/pay` and `POST /bookings/{id}/confirm` per booking (or loop in the app).

**Single-slot response (201):** unchanged — `data` is one booking object (when using `slot_id` or a single id in `slot_ids`).

Pricing default: `court.price_per_hour × slot duration` per slot unless `total_amount` is sent by staff.

Next steps (existing API):

- If there is a remaining balance, collect payment and call `POST /bookings/{booking}/pay` or confirm depending on your flow.
- `POST /bookings/{booking}/confirm` — confirm pending booking (see `BookingController` and policy).

**Receipt / Booking Confirmed screen:** `GET /bookings/{id}` or `GET /bookings/{id}/screen/confirmed` for a screen-oriented payload.

---

### 2.6 Bookings list (filters: date + indoor type)

`GET /bookings?date=YYYY-MM-DD&indoor_facility_kind=court&branch_id=1`

| Query | Purpose |
|-------|---------|
| `date` | Filter by booking date. |
| `branch_id` | Restrict to branch (must be allowed for the user). |
| `indoor_facility_kind` | `court` or `net` — filters via related court. |
| `all=1` | **Required for staff** (`manager` / `admin` / `super_admin`) to list **everyone’s** bookings in scope. Without `all=1`, the API returns only the **current user’s** bookings (same as players). |

Each item includes amounts (`amount`, `advance_amount`, `remaining_amount`), `customer_*`, `court`, `slot`.

---

### 2.7 My Account

| UI | Endpoint |
|----|----------|
| Email, user name, phone | `GET /user` |
| Logout | `POST /logout` |

---

## 3. Recommended booking flow (client)

1. **Login** → store token.
2. **Home** → `GET /screens/home` for user + today’s timeline + branches preview.
3. **Indoor type** → `GET /indoor-types`.
4. **Branch** → from `branches_preview` or `GET /branches`.
5. **Slot board** → `GET /branches/{branch}/slot-board?date=…&indoor_facility_kind=…`.
6. **Select** a slot with `is_booked: false`.
7. **Create booking** → `POST /bookings` with `court_id`, `slot_id` or `slot_ids`, `date`, customer fields, `advance_amount`, optional `total_amount` for staff.
8. **Receipt** → `GET /bookings/{id}` or confirmation screen routes.

---

## 4. History

- `GET /users/{id}/history` — bookings + activity for that user (`id` must be self unless staff).

---

## 5. Time display (avoid UI bugs)

The API exposes slot times as **`start_time` / `end_time`** strings (`HH:MM`, 24-hour). **Always format on the device** (e.g. “5PM to 7PM”, “9 AM – 10 AM”). Do not invent labels like “13 AM” or “0 AM”; use a proper date/time library and 12-hour locale rules.

`GET /slots/times` returns a **catalog** of time strings (intended for admin/quick pick UIs); real availability is per court/day via `slot-board` or `courts/{court}/slots`.

---

## 6. Currency and locale

`GET /app/config` exposes `currency_symbol` (e.g. `Rs.`) and `currency_code`. Amount fields in booking JSON are **strings** with two decimal places — parse as decimal in the app.

---

## 7. Screenshot checklist (attach your PNGs under `docs/screenshots/`)

| # | Screen (from design) | Primary endpoints |
|---|----------------------|-------------------|
| 1 | Splash | `GET /app/config` |
| 2 | Login | `POST /login` |
| 3 | Home | `GET /screens/home`, `GET /bookings/today` |
| 4 | New Booking — indoor type | `GET /indoor-types`, `GET /branches` |
| 5 | New Booking — date / slots | `GET /branches/{branch}/slot-board` |
| 6 | Booking confirmation modal | `POST /bookings` |
| 7 | Booking Confirmed / receipt | `GET /bookings/{id}`, `GET /bookings/{id}/screen/confirmed` |
| 8 | Bookings list | `GET /bookings?date=&indoor_facility_kind=` |
| 9 | Slots Details | Same as slot-board + filters |
|10 | My Account | `GET /user`, `POST /logout` |

---

## 8. Backend changes introduced for mobile parity

- **`courts.indoor_facility_kind`**: `court` | `net` — powers the **Court vs Net** dropdown separately from `court.type` (Indoor/Outdoor).
- **`bookings.customer_name` / `customer_phone`**: guest-facing fields for receipts and lists.
- **`POST /bookings`**: optional `total_amount` for **staff**; optional customer fields.
- **New routes**: `GET /indoor-types`, `GET /user`, `GET /branches/{branch}/slot-board`, `GET /bookings/today`, `GET /app/config`.
- **`GET /screens/home`**: now returns full `user`, plus `todays_booking_timeline`.
- **`GET /bookings`**: query filters `date`, `branch_id`, `indoor_facility_kind`.
