# Military Task Manager — Backend

> A secure, role-aware mission assignment system for military units — with real-time task visibility and Google Calendar integration.

---

## 📌 Overview

Military Task Manager is a RESTful API backend designed to help military units create, manage, and assign missions to personnel. Assigned soldiers can view their tasks both on the web dashboard and directly in **Google Calendar**, ensuring everyone stays informed without manual communication overhead.

The system is built with security in mind: account registration requires **admin approval** before access is granted, preventing unauthorized individuals from entering the system and enabling accurate tracking of active, discharged, or resigned personnel.

---

## ✨ Features

- **Mission Management** — Create and manage missions with details such as title, description, date, and location
- **Manual Personnel Assignment** — Assign one or multiple soldiers to a mission
- **Google Calendar Integration** — Assigned personnel automatically receive the mission as a Google Calendar event
- **Admin Approval Flow** — New account registrations must be approved by an admin before gaining system access
- **Account Lifecycle Tracking** — Admins can monitor active accounts and remove those belonging to discharged or resigned personnel
- **Role-Based Access** *(planned)* — Differentiated access levels for admins, officers, and enlisted personnel
- **Auto-Assignment** *(planned)* — Intelligent mission assignment based on availability and rank

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP 8.2+) |
| Database | PostgreSQL |
| Containerization | Docker + Docker Compose |
| Calendar Integration | Google Calendar API |
| API Style | RESTful JSON API |
| Auth | Admin-approval registration *(JWT/Sanctum — in progress)* |

---

## 🚀 Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+
- Composer

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/krittapastrycode/military-task-manager-backend.git
cd military-task-manager-backend

# 2. Copy environment file
cp .env.example .env

# 3. Configure your .env (database, Google Calendar credentials, etc.)

# 4. Start with Docker
docker-compose up -d

# 5. Install dependencies
docker-compose exec app composer install

# 6. Generate app key
docker-compose exec app php artisan key:generate

# 7. Run migrations
docker-compose exec app php artisan migrate --seed
```

---

## 🔌 API Endpoints

### Missions
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/missions` | List all missions |
| `POST` | `/api/missions` | Create a new mission |
| `GET` | `/api/missions/{id}` | Get mission details |
| `PUT` | `/api/missions/{id}` | Update a mission |
| `DELETE` | `/api/missions/{id}` | Delete a mission |

### Assignments
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/missions/{id}/assign` | Assign personnel to a mission |
| `DELETE` | `/api/missions/{id}/assign/{userId}` | Remove assignment |

### Auth *(in progress)*
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register (pending admin approval) |
| `POST` | `/api/auth/login` | Login |
| `GET` | `/api/admin/pending` | List pending registrations |
| `POST` | `/api/admin/approve/{id}` | Approve a registration |

---

## 🔐 Security Design

The system uses a **two-step account lifecycle**:

```
Register → Pending (admin review) → Approved → Active
                                              ↓
                              Discharged/Resigned → Deactivated/Deleted
```

This ensures that only verified personnel have system access, and that accounts belonging to former members are promptly removed — critical for operational security.

---

## 📅 Google Calendar Integration

When a soldier is assigned to a mission, the system:
1. Calls the Google Calendar API using a service account
2. Creates a calendar event with mission details (title, date, location, description)
3. Adds the assigned personnel as event attendees

> Requires Google Calendar API credentials configured in `.env`

---

## 🗂 Project Structure

```
├── app/
│   ├── Http/Controllers/    # API controllers
│   ├── Models/              # Eloquent models
│   └── Services/            # Google Calendar service, etc.
├── database/
│   ├── migrations/          # DB schema
│   └── seeders/             # Test data
├── docker/                  # Docker config files
├── routes/
│   └── api.php              # API route definitions
├── Dockerfile
└── docker-compose.yml
```

---

## 🗺 Roadmap

- [x] Mission creation and management
- [x] Manual personnel assignment
- [x] Google Calendar sync
- [x] Docker containerization
- [ ] JWT/Sanctum authentication
- [ ] Admin approval flow for registrations
- [ ] Account deactivation on discharge/resignation
- [ ] Role-based access control (Admin / Officer / Enlisted)
- [ ] Smart auto-assignment based on rank and availability
- [ ] Mobile-friendly frontend

---

## 👨‍💻 Author

**Krittapas Polmanee**
Backend Engineer · Laravel · PostgreSQL · Docker
[GitHub](https://github.com/krittapastrycode) · [LinkedIn](https://www.linkedin.com/in/กฤตภาส-พลมณี-b387b6294/)

---

## 📄 License

This project is licensed under the MIT License.
