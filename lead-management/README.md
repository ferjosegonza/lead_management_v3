# Lead Management System

## Project Overview
The Lead Management System is a simple application that allows users to submit leads through a form. Each new lead is stored in a MySQL database and an external system is notified about the new lead.

## Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- Composer
- MySQL
- Slim Framework

### Running the Application Locally

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd lead-management
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create the database:
   ```sql
   CREATE DATABASE lead_management;
   ```

4. Set up the database table:
   ```sql
   CREATE TABLE leads (
     id INT AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(50) NOT NULL,
     email VARCHAR(100) NOT NULL UNIQUE,
     phone VARCHAR(20),
     source ENUM('facebook', 'google', 'linkedin', 'manual') NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

5. Copy the `.env.example` to `.env` and fill in the required environment variables.

6. Start the server:
   ```bash
   php -S localhost:8000 -t public
   ```

7. Access the application at `http://localhost:8000`.

## API Details

### Lead Submission Endpoint
- **POST** `/leads`
- **Request Body**:
  ```json
  {
    "name": "string (required, min 3, max 50 characters)",
    "email": "string (required, must be a valid email, unique in the database)",
    "phone": "string (optional)",
    "source": "facebook | google | linkedin | manual (required)"
  }
  ```

### External Notification
When a new lead is created, the application will notify an external system using the following API:
- **POST** `https://mock-api.com/marketing-webhooks`
- **Data to Send**:
  ```json
  {
    "lead_id": "lead's id",
    "name": "lead's name",
    "email": "lead's email",
    "source": "lead's source"
  }
  ```

## Error Handling
If the external API responds with a 400 or 500 error, the application will retry 3 times with a 2-second delay between attempts. If it still fails, the error will be logged to `logs/error.log`.

## Optional: Containerization with Docker
If you are familiar with Docker, you can run the application in a containerized environment. 

1. Build and run the containers:
   ```bash
   docker-compose up --build
   ```

2. Access the application at `http://localhost:8000`.

## Submission Instructions
- GitHub repository with:
  - Source code.
  - `README.md` with setup instructions.
  - `.env.example` file.
