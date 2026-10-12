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

Enquiry forms open WhatsApp (`+91 7039152173`) with the filled details.

## Preview locally

```bash
python3 -m http.server 8080
```

Open http://localhost:8080

## Deploy on Hostinger

Upload every file in this folder into `public_html` (replace the current site only when you are ready). Pretty URLs such as `/about` work because each page is an `index.html` inside its own folder.
