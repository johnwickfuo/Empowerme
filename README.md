# EmpowerME Grant Program — PHP/MySQL build

This package is designed for a normal PHP 8.1+ / MySQL or MariaDB website and works well with HestiaCP. It uses no PHP framework and no Composer packages.

## Included features

- Responsive public website: Home, About Us, Apply, Track Application, Contact Us, Privacy Policy, Terms of Use.
- Location chooser: USA, UK, Canada, Australia, Others.
- Admin form builder with locked Full Name + Email fields on every form.
- Custom text, phone, number, date, long-text, dropdown, radio, checkbox, file upload, and heading/instruction fields.
- Simple conditional form logic: equals, does not equal, empty, not empty.
- One active form per location; unlimited saved forms and automatic form versioning.
- One application per email address.
- 16-character alphanumeric tracking codes with no punctuation.
- Complete applicant update history.
- Applicant responses and file uploads from the tracking page.
- Maximum 20 MB per uploaded file. The app intentionally does not restrict file extensions; uploaded files are renamed to random `.dat` files before storage so uploaded PHP/scripts are not executable as their original type.
- Admin-created statuses; there are no default statuses.
- Admin update messages with optional HTTPS button/link.
- SMTP settings in the admin area; no external mail library required.
- Professional HTML emails for new applications and application updates.
- Contact form saved to MySQL and emailed when SMTP is configured.
- Search/filter applications by code/name/email, location, status, date, archive state.
- CSV export.
- Archive and permanent delete.
- Dashboard totals and breakdowns by status/location.
- Editable public organization/contact/legal text.

## Install on HestiaCP

1. Create a MySQL/MariaDB database and database user in HestiaCP.
2. Upload **all contents of this package** into the domain's `public_html` directory.
3. Make sure PHP can write to `inc/` during setup and to `storage/` afterward. Normal Hestia ownership normally already permits this.
4. Open `https://yourdomain.com/setup.php` in your browser.
5. Enter the MySQL database host/name/username/password and your full `https://...` website URL.
6. The installer creates the tables and writes `inc/config.php`.
7. After the success screen, **delete `setup.php` from the server**.
8. Visit the private admin URL below, create statuses, create one or more forms, and make the desired form active for each location.
9. Configure SMTP under **Settings & Email**, save it, then use the test-email tool.

### Private admin URL

`/gadmin8f3k2p9x7m4q6v1c/`

There is deliberately no password/login on this area because that was part of the requested specification. The public website does not link to it. Anyone who learns this URL can access the administration area, so do not publish it or include it in public screenshots/messages.

## Email settings

The custom SMTP client supports:

- STARTTLS/TLS (commonly port 587)
- SSL (commonly port 465)
- No encryption where your server/provider specifically requires it
- SMTP AUTH LOGIN

The SMTP password is encrypted with the random application key generated during installation before being stored in MySQL.

## File uploads

The application enforces 20 MB maximum **per file**. `.user.ini` requests PHP settings of `upload_max_filesize=20M`, `post_max_size=128M`, and `max_file_uploads=20`. Some servers override per-directory PHP values, so if uploads fail before reaching the application, change these PHP values in HestiaCP/PHP-FPM as well.

Uploaded files are stored in `storage/uploads/` using random `.dat` filenames. The original name, MIME type, and size are stored in MySQL. Applicants/admins download files through PHP routes rather than using the storage filename directly.

## First admin setup

A good order is:

1. Settings & Email — confirm program contact details and set SMTP.
2. Statuses — create whatever application stages you want.
3. Forms — create a form for each location you want open.
4. Mark one form active for each open location.
5. Submit one test application from the public site.
6. Open the test application in the admin area, change its status, add a link, and verify the notification email and tracking timeline.
7. Delete the test application when finished.

## Image sources

The design uses your supplied EmpowerME logo. Two professional Pexels photographs are loaded from Pexels URLs on the homepage/about page. See `CREDITS.md` for source details. You can replace them with local images at any time.

## Important deployment notes

- HTTPS should be enabled before accepting real applicant data.
- Keep regular MySQL and `storage/uploads/` backups.
- The tracking code is the applicant's only credential by design. The generator uses cryptographically secure randomness and excludes punctuation.
- Do not expose `inc/config.php`. The included Apache `.htaccess` blocks `inc/` and `storage/`; PHP config files also execute rather than display when PHP is configured correctly.
- If your Hestia configuration serves static files directly through Nginx, the randomized `.dat` storage names prevent uploaded scripts from executing as PHP. For additional hardening you can add a Nginx rule denying direct access to `/storage/`.
- Application update links accept only valid `https://` URLs.
- The site text does not claim government affiliation, nonprofit registration, or guaranteed funding.
- Terms state that any fee must be disclosed before payment and that payment does not guarantee approval or funding.

## Testimonials and sponsors

The public website now includes a Testimonials page and a Sponsors page. The homepage automatically shows the four most recently published testimonials and up to twelve recent sponsors.

In the private admin dashboard:
- **Testimonials** lets you add, edit, replace images, or delete beneficiary testimonials. Image, beneficiary name, and story are all optional. Completely blank testimonials remain hidden publicly until content is added.
- **Sponsors** lets you add, edit, replace logos, or delete sponsors. Sponsor name and logo are required.
- Public testimonial and sponsor images are stored in protected `storage/media` and are served through `media.php`. JPG, PNG, WEBP, and GIF images up to 20 MB are accepted.

### Updating an existing installation

If EmpowerME was already installed before this update, upload/overwrite the project files with this package. The application automatically creates the new `testimonials` and `sponsors` tables the next time the site loads; you do not need to rerun `setup.php`. The existing applications, forms, statuses, settings, and tracking data are left unchanged.
