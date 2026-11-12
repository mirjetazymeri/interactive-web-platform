# Demo Project

A web platform built with **Laravel and MySQL** on the backend and **Next.js and TypeScript** on the frontend.

The project combines a public website, an administration panel, API-driven content and layout configuration, and an integrated chatbot with optional OpenAI API integration.

## Technology Stack

| Component | Technology | Purpose |
| --- | --- | --- |
| Backend | Laravel, PHP | Business logic, administration panel, and API |
| Database | MySQL | Content, settings, and application data |
| Frontend | Next.js, React, TypeScript | Public website and user interactions |
| Chatbot | Deterministic logic | Responses based on predefined rules and available content |
| Optional AI Integration | OpenAI API | AI responses when configured and enabled |

## Architecture

The platform consists of two separate applications:

- **Backend:** manages content, application settings, integrations, and API endpoints.
- **Frontend:** retrieves data and configuration from the backend API and renders the public website.

Both applications can be developed and deployed independently.

## Backend

The Laravel backend provides a centralized administration panel for managing:

- Pages and website content.
- Services and projects.
- Widgets and page sections.
- Translations and multilingual content.
- SEO metadata.
- Theme and presentation settings.
- Contact form configuration.
- Chatbot settings.
- OpenAI integration settings.

MySQL stores the content and configuration used by the application.

## Frontend

The frontend uses Next.js and TypeScript to provide a responsive website connected to the Laravel API.

Its main features include:

- Dynamic pages and reusable sections.
- Service and project presentation.
- Multilingual content.
- Responsive layouts.
- Animations and interactive components.
- Contact forms.
- An integrated chatbot.

## API-Driven Design and Page Structure

The frontend uses the backend API for both content and presentation settings.

Through the administration panel and the settings exposed by the API, administrators can configure the supported frontend design options and page structure, including:

- Theme colors and typography.
- Logos and visual assets.
- Navigation and footer content.
- Page sections and widget placement.
- Section ordering and visibility.
- Titles, descriptions, images, and links.
- Translations and SEO information.

This allows the frontend to reflect configuration changes without requiring code edits for each content or layout update.

The available controls depend on the components and configuration options implemented in the project. New component types or custom behavior may require development.

## Integrated Chatbot

The platform includes a chatbot that helps visitors find information about services, projects, website content, and contact options.

### Default Mode

The chatbot operates using deterministic logic and predefined content.

In this mode:

- No OpenAI connection is required.
- No paid AI API requests are made.
- Responses follow configured rules and available information.
- Visitors can be directed to contact options when a suitable answer is unavailable.

### Optional OpenAI Integration

The backend includes a settings area for configuring OpenAI API integration.

The administration panel provides a central place to manage integration credentials, supported parameters, and activation status.

**OpenAI integration is disabled by default.** Adding integration settings does not automatically enable AI requests.

When enabled, the request flow is:

1. A visitor submits a message through the frontend chatbot.
2. The frontend sends the message to the Laravel backend.
3. The backend processes the request using the configured OpenAI integration.
4. The response is returned to the frontend and displayed in the chatbot.

The OpenAI API key must remain on the backend. It must never be exposed in browser code or public frontend environment variables.

API usage may incur charges after the integration is enabled.

## Installation

The following steps describe a standard local setup. Run commands inside the corresponding backend or frontend application directory.

Directory names, required versions, environment variable names, and additional setup steps should follow the repository’s actual configuration.

### Prerequisites

Install:

- PHP and the extensions required by `composer.json`.
- Composer.
- MySQL.
- Node.js compatible with `package.json`.
- The package manager used by the frontend.
- Git.

### 1. Clone the Repository

Replace the example URL with the actual repository URL:

```bash
git clone <repository-url>
cd <repository-directory>
```

If the backend and frontend are maintained in separate repositories, clone both and configure each application independently.

### 2. Install the Laravel Backend

Open the backend directory and install PHP dependencies:

```bash
composer install
```

Create the local environment file:

```bash
cp .env.example .env
```

On Windows Command Prompt, use:

```bat
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create a MySQL database and update the backend `.env` file:

```dotenv
APP_NAME="Demo Project"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo_project
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Run database migrations:

```bash
php artisan migrate
```

If the project includes seeders for initial content or administrator setup, follow the repository’s seeding instructions. Where appropriate:

```bash
php artisan db:seed
```

If the application uses Laravel’s public storage disk, create the storage link:

```bash
php artisan storage:link
```

If the administration panel includes assets compiled through Vite, install and build those assets using the backend’s package scripts:

```bash
npm install
npm run build
```

Start the backend:

```bash
php artisan serve
```

The default local backend address is:

```text
http://localhost:8000
```

### 3. Install the Next.js Frontend

Open the frontend directory and install dependencies using the package manager associated with its lockfile.

For an npm-based project:

```bash
npm install
```

Create `.env.local` using the provided environment example, if available.

Set the backend API URL using the environment variable expected by the frontend. For example:

```dotenv
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

This variable name and API path are examples. Use the exact name and route prefix referenced by the project.

Start the development server:

```bash
npm run dev
```

The default local frontend address is:

```text
http://localhost:3000
```

### 4. Connect the Applications

Confirm that:

- The frontend points to the correct Laravel API URL.
- The backend allows requests from the frontend origin.
- Required content and settings exist in the database.
- Public media URLs are accessible.
- Authentication settings are configured if protected API routes are used.

Restart the relevant development server after changing environment variables.

### 5. Configure the Website

Access the administration panel using the route and administrator setup provided by the project.

Configure the website’s content, theme settings, navigation, pages, and widget structure. The frontend retrieves the supported configuration through the API.

Keep OpenAI integration disabled unless AI responses are required and valid credentials have been configured.

## Production Deployment

Deploy the backend and frontend with their respective production environment settings.

### Laravel Backend

Typical production preparation includes:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Configure the web server to serve Laravel from its `public` directory.

Set production values such as:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-backend-domain.com
```

Configure database access, file permissions, email delivery, and any queues or scheduled tasks used by the application.

### Next.js Frontend

Set the production API URL, then build the frontend:

```bash
npm run build
```

For deployments using the standard Next.js Node.js server:

```bash
npm run start
```

Use the deployment method appropriate to the project’s Next.js configuration and hosting environment. Public environment variables must be set before building.

## Environment Variables and Credentials

Keep private configuration outside version control.

Do not commit:

- `.env` or `.env.local` files.
- Database passwords.
- OpenAI API keys.
- SMTP credentials.
- Other private service credentials.

Use `.env.example` files to document required variables without including real secrets.

## Project Purpose

Demo Project demonstrates a decoupled web platform with centralized content management, API-driven frontend configuration, and an integrated chatbot that supports deterministic responses and optional OpenAI integration.
