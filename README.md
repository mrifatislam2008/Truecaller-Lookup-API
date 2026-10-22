# Phone Lookup API (PHP) — Vercel

A starter project for deploying a PHP phone-lookup API on Vercel using the community-maintained `vercel-php` runtime.

## Project structure

```text
.
├── api/
│   └── index.php
├── vercel.json
└── README.md
```

## Deploy using GitHub

1. Create a GitHub repository, for example `phone-lookup-api`.
2. Upload the files and folders in this project to the repository root. Do not upload the ZIP as the only repository file.
3. Open the Vercel dashboard and select **Add New → Project**.
4. Import the GitHub repository.
5. Keep the Root Directory as `./`, then deploy.
6. In Vercel, open **Project → Settings → Environment Variables** and add the variables below.
7. Redeploy after setting environment variables.

## Environment variables

Set these in Vercel, not in source code:

- `LOOKUP_API_URL` — HTTPS endpoint of a phone-data provider you are authorized to use.
- `LOOKUP_API_TOKEN` — API token issued by that provider.
- `LOOKUP_RATE_LIMIT_SECONDS` — `6`
- `RATE_LIMIT_SECRET` — a long random secret used to hash client identifiers.

Do not commit API tokens, session cookies, or `.env` files to GitHub.

## Test endpoint

After deployment, try:

`https://YOUR-PROJECT.vercel.app/api/index.php?phone=%2B8801824123456`

Replace `YOUR-PROJECT` with your actual Vercel deployment domain.

## Important notes

- This project does not include a phone-data provider or credentials. A successful deployment does not guarantee that a lookup will return a person's name or other data.
- Configure only a provider and API endpoint that you are authorized to use. This template does not call private Truecaller endpoints or scrape Truecaller pages.
- The `vercel-php@0.9.0` runtime is community-maintained, not a first-party Vercel PHP runtime. Confirm its current compatibility before production use.
- Vercel serverless temporary storage is ephemeral and instance-local. The included file-based rate limit is best-effort; use a shared database or Redis service for a robust global rate limit.
- The PHP code requires PHP cURL support and a valid CA certificate bundle for TLS verification.
