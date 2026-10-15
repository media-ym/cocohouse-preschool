# DPDP progress

Branch: `compliance/dpdp` (local only, not pushed).
Notice version: `2026-10-15-draft`.
This file is an engineering log. It is not legal advice and it is not a claim that the site complies with the Digital Personal Data Protection Act, 2023.

## What was built

- `/privacy-notice` — notice: what data, why, proposed retention, third parties, rights (access, correct, erase, withdraw), grievance contact.
- `/privacy-policy` — privacy policy (children, consent, cookies, security, transfers, rights). `/privacy` redirects here.
- `/terms-and-conditions` — terms, including a data-protection clause. `/terms` redirects here.
- `/data-rights` — request form for access, correction, erasure, or withdrawal. Saved even if email fails.
- Unticked, per-purpose consent on all seven enquiry forms (home, visit, popup, admissions, daycare, contact, special education).
- Consent stored on the enquiry and in `storage/consents.json`. Rights requests in `storage/data-rights.json`. Both are gitignored. IP address is not stored.
- Google Analytics, Google Tag Manager, and Microsoft Clarity load only after “Allow analytics”. Default is off. Choice is kept in `coco_consent`.
- Grievance Officer: Mrs. Pratheeksha Darak, with `info@cocohouse.in` and `+91-7039152173`, in the footer, the privacy pages, the 404 page, and the login page.
- Admin enquiry card shows which purposes were granted.
- `.htaccess` redirects `http://cocohouse.in` and `http://www.cocohouse.in` to `https://cocohouse.in`.
- `BREACH_RUNBOOK.md` — draft 72-hour Board notice and affected-user notice templates.
- The four legal URLs are added to the sitemap helper.

## Inventory

### Personal data collected on the site

| Place | Data | Stored |
| --- | --- | --- |
| Home admission form (`admissionForm`, type `home_hero`) | Parent name, phone, child name, age, program | `storage/enquiries.json` |
| Home school-visit form (`visitForm`, type `school_visit`) | Parent, phone, child, preferred time | same |
| Entry popup (`cocoPopupForm`, type `popup`) | Parent, phone, child, program | same |
| Admissions page (`admissionAdsForm`, type `admission_page`) | Parent, phone, child, age, program, plus ad click ids if present in the URL | same |
| Daycare (`daycareForm`, type `daycare`) | Parent, phone, child, age, message | same |
| Contact (`contactForm`, type `contact`) | Name, phone, email, message | same |
| Special education (`specialForm`, type `special_education`) | Parent, phone, child, age, and a program value that can be a condition the parent selects (Autism, ADHD, speech, learning difficulty, behaviour, Down syndrome, developmental delay) | same, plaintext |
| Data-rights form | Name, phone, optional email, request type, lookup detail | `storage/data-rights.json` |
| Blog view counter | Cookie `coco_blog_seen` (slug list, 24 hours). No name. | `storage/blog-views.json` |
| Admin login | Staff username and session cookie `coco_admin` | `storage/users.json` (password hash only) |

Enquiry mail goes to `cocohousepreschool@gmail.com` and `info@cocohouse.in`.

### Trackers and third parties

| Service | Role | Gated |
| --- | --- | --- |
| Google Analytics `G-P73Y2NSCBR` | Page and event measurement | Yes, after Allow analytics |
| Google Tag Manager `GTM-WFQW2G5B` | Tag container. Contents of the container are unknown. If it also fires GA4, counts can double. | Yes |
| Microsoft Clarity `yy6paa12av` | Session recording of how the page is used | Yes |
| Google Fonts | Poppins typeface | No. Documented. Needed to render the site. |
| YouTube thumbnails | Gallery and testimonial pictures | Proxied by `/api/yt-thumb.php`. The browser does not call YouTube for the picture. Play still loads YouTube. The school states these videos and thumbnails are published with the parent's consent. |
| WhatsApp (`wa.me/917039152173`) | Click-to-chat, and the form fallback if the site cannot deliver the enquiry | Disclosed. Fallback runs only after the required enquiry consent box is ticked. |
| Google Maps share link | Directions | No. Outbound link. |
| Facebook, Instagram, YouTube channel | Outbound social links | No |
| schema.org JSON-LD | Structured data in the page | No network call by itself |
| Hostinger | Hosts the site and the JSON files | Processor / host |
| PHP `mail()` | Sends enquiry and rights mail to the two school inboxes | Disclosed |

No Facebook Pixel was found. No captcha widget was found.

Events (`generate_lead`, `phone_click`, `whatsapp_click`, `email_click`, `directions_click`, `admission_click`, `popup_open`) do not include name, phone, or email. They do not push to `dataLayer` until analytics consent is granted.

## Decisions

- Legal pages are standalone PHP, not CMS pages. An unknown CMS page id would canonicalize to the homepage.
- Every legal page shows “Draft for lawyer review”.
- Grievance Officer name was given by the school: Mrs. Pratheeksha Darak. Email and phone stay the published school contacts.
- Enquiry consent (parent/guardian, contact details, child name and age) is required to submit. Admission-update consent is optional and starts unticked. Special-education support consent is required only on that form. Analytics consent is a separate banner and is not required to enquire.
- A ticked checkbox is not treated as verifiable parental consent under DPDP section 9.
- Retention written on the notice is a proposal: enquiry and consent records while the enquiry is open and not longer than 24 months after last contact, unless a law requires longer. Rights requests kept until answered, then up to 24 months as proof. Google and Microsoft retention is whatever is set in those accounts.
- Consent log is linked by enquiry id and notice version. IP is not stored.
- WhatsApp fallback is the same enquiry text, already covered by the required enquiry box and the notice. No third checkbox was added for that failure path.
- HTTPS redirect matches only `cocohouse.in` and `www.cocohouse.in`, so the local PHP server is unchanged.
- The blog-view cookie stays. It is a functional counter, not a marketing tracker. `Secure` is set when the request is already HTTPS.

## Security

- Each enquiry and data-rights form shows a picture code. The server checks it. A wrong or reused code is rejected. The old honeypot field is still there.
- Enquiry, consent, and data-rights files are encrypted with AES-256-GCM before they are written. If encryption fails, the form is not saved. The key is `storage/data.key` (gitignored). Back this file up. If it is lost, old enquiry files cannot be opened. Do not upload this key over a live server that has already created its own key, and do not upload the local enquiry file over live enquiries.
- Mail to the school inbox is still ordinary email. Encryption covers the files on the server, not the email.
- The session cookie is `Secure` only when the request is already HTTPS.
- Special-education condition labels are inside the encrypted enquiry file. They are still visible to staff in the admin, and they are still in the notification email.
- `storage/` is blocked by `.htaccess` and the local router.

### HTTPS, in plain words

The public address is already `https://cocohouse.in` (the lock icon). The new `.htaccess` rule only does this: if someone opens `http://cocohouse.in` or `http://www.cocohouse.in` without the lock, Apache sends them to `https://cocohouse.in`. That rule runs on Hostinger after `.htaccess` is uploaded. It does not run on the local preview, because the PHP preview server does not read `.htaccess`. The live site does not update from git by itself.

## Needs a lawyer

- All copy on `/privacy-notice`, `/privacy-policy`, `/terms-and-conditions`, and `/data-rights`. The public pages no longer show a draft banner. A lawyer should still review the wording.
- Whether the school is the correct Data Fiduciary, and whether a consent manager is required.
- Whether the parent checkbox meets the verifiable-consent rule for children’s data.
- The 24-month retention proposal.
- Cross-border processing by Google, Microsoft, Meta, and the host.
- The breach templates in `BREACH_RUNBOOK.md`, including the 72-hour Board notice.
- The school named Mrs. Pratheeksha Darak as Grievance Officer. A lawyer should confirm that appointment and the contact details.
- Any damages or liability wording. The terms draft does not set a cap.

## Open items

- Verifiable parental consent for a child’s name and age on an enquiry form is still only a checkbox. A lawyer has to say if that is enough.
- The school states that uploaded videos and YouTube thumbnails are published with parent consent. This repo does not store those consent forms.
- GA4, GTM, and Clarity retention and region settings must be set in those consoles. The GTM container was not inspected.
- Pressing play still loads YouTube in the visitor’s browser.
- HTTPS redirect is in the repo only until `.htaccess` is uploaded to Hostinger. This branch was not pushed.
- Staff still erase an enquiry from the admin enquiries screen. The public rights form records the request; it does not delete the record by itself.
- Keep a backup of `storage/data.key`.
