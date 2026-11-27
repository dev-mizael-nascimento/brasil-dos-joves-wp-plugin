=== Projeto Brasil dos Jovens (PJB) - Gestão Social & Acadêmica ===
Contributors: MizaelgNascimento
Tags: nonprofit, courses, certificates, scheduling, social-assistance
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Social assistance, vocational courses, class attendance, appointment scheduling, and digital certificates with QR Code verification for nonprofits.

== Description ==

**Projeto Brasil dos Jovens (PJB) - Gestão Social & Acadêmica** turns WordPress into a complete management platform for non-governmental organizations (NGOs), social projects, and vocational training centers:

* **Beneficiary Management**: Complete registration with socioeconomic screening, per capita income calculation, schooling status, and social vulnerability level.
* **Vocational Courses & Classes**: Course management with workload hours, target age range, minimum schooling requirements, syllabus topics, class schedules, and seat capacity.
* **Attendance & Class Roll**: Digital attendance log with automatic attendance rate calculation, dropout alerts, and printable A4 class rolls.
* **Digital Certificates with QR Code (Front & Back)**: Instant certificate issuance with HMAC-SHA256 cryptographic signature, automatic course syllabus on the back page, public verification portal, and read-only CPF lookup for second copies.
* **Appointment Scheduling**: Management of health, legal, psychological, and social professionals with configurable consultation duration, weekly or date-specific availability, and multi-step public booking.
* **Sports, Culture, Donations & Finance**: Management of workshops, social campaigns, food basket inventory, and financial cash flow.
* **Communication & AI (Optional)**: Optional Google Gemini integration for social assessments and grant proposals, plus notifications via SMTP or WhatsApp click-to-chat links.
* **Public Shortcodes**: `[pjb_inscricao]`, `[pjb_agendamento]`, `[pjb_validar]`, and `[pjb_painel]`.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Click **Upload Plugin** and select `pjb-gestao.zip` (or install directly from the WordPress.org directory).
3. Click **Install Now** and then **Activate Plugin**.
4. Open the **PJB Gestão** menu in your WordPress admin sidebar.
5. (Optional) Add `[pjb_inscricao]`, `[pjb_agendamento]`, or `[pjb_validar]` to your public pages.

== Frequently Asked Questions ==

= Are issued certificates protected against tampering? =
Yes. Every certificate receives a unique registration code, an HMAC-SHA256 cryptographic signature, and a QR Code pointing to the public verification portal (`/validar` or any page using `[pjb_validar]`).

= Can students download a second copy of their certificate on their own? =
Yes. On the public validation page (`[pjb_validar]`), students can search by their CPF and print or save the front and back of their issued certificate in read-only mode.

= Is the Google Gemini AI integration required? =
No. All core modules work 100% offline without any API key. The Google Gemini integration is completely optional.

== External Services ==

This plugin may connect to the following external services when configured or triggered by the user:

1. **Google Gemini API (`https://generativelanguage.googleapis.com`)**
   - **Purpose:** Optional generation of social assessment drafts, impact reports, and grant proposals.
   - **When data is sent:** Only when an administrator saves a Google Gemini API key in Settings and explicitly clicks an AI generation button.
   - **Terms & Privacy:** [Google AI Terms](https://ai.google.dev/gemini-api/terms) | [Google Privacy Policy](https://policies.google.com/privacy)

2. **QRServer API (`https://api.qrserver.com`)**
   - **Purpose:** Generates QR Code image URLs pointing to the public certificate validation link.
   - **When data is sent:** When issuing or displaying a certificate record with a validation URL.
   - **Info:** [QRServer API](https://goqr.me/api/)

3. **WhatsApp Click-to-Chat (`https://api.whatsapp.com`)**
   - **Purpose:** Opens a pre-filled WhatsApp message link for enrollment, scheduling, or certificate confirmation.
   - **When data is sent:** Only when the user explicitly clicks the WhatsApp action button.
   - **Terms:** [WhatsApp Legal](https://www.whatsapp.com/legal/)

== Source Code ==

The administrative and public user interface of this plugin is built with React, TypeScript, Vite, and Tailwind CSS. The complete unminified source code (`src/`) and build configuration (`package.json`, `vite.config.ts`) are publicly available:

* Repository: https://github.com/dev-mizael-nascimento/PJB-brasil-dos-joves-
* Build command for `build/`: `npm install && npm run build`

== Screenshots ==

1. Executive Dashboard with social indicators, charts, and active classes.
2. Courses & Classes module with curriculum syllabus and class attendance.
3. Official Front & Back Certificate (A4 Landscape) with syllabus and QR Code.
4. Multi-step public enrollment and appointment scheduling portals with CPF lookup.

== Changelog ==

= 1.2.0 =
* Full compliance with WordPress.org Plugin Directory guidelines and Plugin Check (PCP).
* Externalized `react` and `react-dom` to use WordPress core scripts.
* Added minimum schooling requirement to courses and schooling status to public enrollment.
* Added native WordPress authentication endpoint (`/auth/login`) and improved CPF lookup.
* Improved Light/Dark mode contrast and print isolation for certificates and vouchers.

= 1.0.0 =
* Initial release of PJB Gestão Social & Acadêmica.

== Upgrade Notice ==

= 1.2.0 =
Compliance update for WordPress.org directory, externalized React/ReactDOM scripts, and course schooling fields.
