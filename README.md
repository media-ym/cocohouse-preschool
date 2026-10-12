# Cocohouse Preschool & Daycare

Multipage website for [cocohouse.in](https://cocohouse.in/) — Home, About, Programs, Daycare, Gallery, Blog, and Contact.

## Pages

- `/` Home
- `/about` About Us
- `/programs` Programs
- `/daycare` Daycare
- `/gallery` Gallery
- `/blog` Blog
- `/blog-post/<slug>` Blog posts
- `/contact` Contact

Enquiry forms are saved for the school team and emailed to cocohousepreschool@gmail.com and info@cocohouse.in. Admin login is at `/admin/`.

## Admin

Open `/admin/` after the site is running on PHP (Hostinger does this). From there you can:

- read and update admission, visit, daycare, and contact enquiries
- add, edit, hide, and delete blog posts
- change the admin password

Parent phone numbers are stored in `storage/enquiries.json` on the server and are not part of the public site.

## Preview locally

```bash
python3 -m http.server 8080
```

Open http://localhost:8080

## Deploy on Hostinger

Upload every file in this folder into `public_html` (replace the current site only when you are ready). Pretty URLs such as `/about` work because each page is an `index.html` inside its own folder.
