# Santa Shuffle
<!-- impeccable:product-schema 1 -->
## Platform
web
## Stack
Laravel, React + TypeScript with Inertia, SQLite by default and optional PostgreSQL. Docker Compose for self-hosting.
## Users
Teams organizing a Secret Santa, initially Romain’s team on his VPS.
## Product Purpose
Create an event, invite colleagues, draw recipients privately, and reveal each gift recipient.
## Capabilities and Constraints
Name, date, budget in EUR, CHF, USD, CAD, GBP, JPY or AUD (integer amounts, no conversion), event message, participant wishes, exclusions. No participant account. Organizer signs in with a single-use email code and gets a personal multi-event dashboard; no password. Organizer must not see assignments through the UI. Open source, simple deployment.
## Brand Commitments
Santa Shuffle. Clear Secret Santa identity. English, French, Spanish and German copy. Visual inspiration: https://www.infomaniak.com/fr/billetterie — airy composition, large typography, warm cream paper, cherry red and pine green with playful stationery animations (updated at user request).
## Product Principles
Keep the workflow short. Protect the surprise. Keep hosting simple. Never fabricate social proof.
## Open decisions
Project source uses the MIT license; third-party licenses remain separate. Maximum 100 participants per event. GitHub repository publication, public package visibility and production deployment require separate operational setup.
