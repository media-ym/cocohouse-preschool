/* ============================================================
   Cocohouse Website — Main JavaScript
   Handles: Navbar, Mobile Menu, FAQ Accordion, Form Submission, Blog Loading
   ============================================================ */

// ====== Navbar scroll effect ======
(function () {
  var navbar = document.querySelector('.navbar');
  if (!navbar) return;
  function onScroll() {
    if (window.scrollY > 30) navbar.classList.add('scrolled');
    else navbar.classList.remove('scrolled');
  }
  window.addEventListener('scroll', onScroll);
  onScroll();
})();

// ====== Mobile menu ======
(function () {
  var toggle = document.querySelector('.nav-toggle');
  var drawer = document.querySelector('.mobile-drawer');
  var overlay = document.querySelector('.drawer-overlay');
  var closeBtn = document.querySelector('.mobile-drawer-close');
  if (!toggle || !drawer) return;

  function open() {
    drawer.classList.add('open');
    if (overlay) overlay.classList.add('show');
  }
  function close() {
    drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
  }
  toggle.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);
  if (overlay) overlay.addEventListener('click', close);
  // Close on link click
  drawer.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', close);
  });
})();

// ====== FAQ Accordion ======
(function () {
  var items = document.querySelectorAll('.faq-item');
  items.forEach(function (item) {
    var btn = item.querySelector('.faq-question');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('open');
      // Close all
      items.forEach(function (i) { i.classList.remove('open'); });
      // Open clicked (if it was closed)
      if (!isOpen) item.classList.add('open');
    });
  });
})();

// ====== Toast notification ======
function showToast(title, desc, type) {
  type = type || 'success';
  var existing = document.querySelector('.toast');
  if (existing) existing.remove();

  var toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.innerHTML =
    '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
    (type === 'success'
      ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
      : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>') +
    '</svg>' +
    '<div><div class="toast-title">' + escapeHtml(title) + '</div>' +
    (desc ? '<div class="toast-desc">' + escapeHtml(desc) + '</div>' : '') + '</div>';

  document.body.appendChild(toast);
  setTimeout(function () { toast.classList.add('show'); }, 10);
  setTimeout(function () {
    toast.classList.remove('show');
    setTimeout(function () { toast.remove(); }, 300);
  }, 4000);
}

function escapeHtml(str) {
  var div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// ====== Form submission helper ======
function setupForm(formId, apiEndpoint, options) {
  options = options || {};
  var form = document.getElementById(formId);
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // Validate required fields
    var required = form.querySelectorAll('[required]');
    for (var i = 0; i < required.length; i++) {
      if (!required[i].value.trim()) {
        showToast('Please fill all required fields', '', 'error');
        return;
      }
    }

    // Validate phone
    var phoneField = form.querySelector('[data-validate="phone"]');
    if (phoneField) {
      var digits = phoneField.value.replace(/\D/g, '');
      if (digits.length < 10) {
        showToast('Invalid phone number', 'Please enter a valid 10-digit mobile number.', 'error');
        return;
      }
    }

    // Collect data
    var data = {};
    var inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(function (input) {
      if (input.name) {
        data[input.name] = input.value;
      }
    });
    // Add type if specified
    if (options.type) data.type = options.type;

    var labels = {
      childName: "Child's Name",
      childAge: "Child's Age",
      program: 'Program',
      parentName: "Parent's Name",
      phone: 'Mobile',
      email: 'Email',
      message: 'Message'
    };
    var typeLabels = {
      admission: 'Admission Enquiry',
      school_visit: 'School Visit',
      daycare: 'Daycare Enquiry',
      contact: 'Contact Enquiry'
    };
    var lines = ['Hello Cocohouse Preschool,'];
    lines.push('Enquiry: ' + (typeLabels[options.type] || 'Website Enquiry'));
    Object.keys(data).forEach(function (key) {
      if (!data[key] || key === 'type') return;
      lines.push((labels[key] || key) + ': ' + String(data[key]).trim());
    });

    var wa = 'https://wa.me/917039152173?text=' + encodeURIComponent(lines.join('\n'));
    window.open(wa, '_blank', 'noopener');
    showToast(
      options.successTitle || 'Submitted!',
      'WhatsApp is opening so you can send this enquiry to our team.'
    );
    form.reset();
  });
}

// ====== Blog listing loader ======
function loadBlogPosts(containerId, options) {
  options = options || {};
  var container = document.getElementById(containerId);
  if (!container) return;

  fetch('/data/blog-posts.json')
    .then(function (res) { return res.json(); })
    .then(function (data) {
      var posts = data.items || [];
      if (posts.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;">No blog posts yet. Check back soon!</div>';
        return;
      }

      if (options.featuredOnly) {
        var featured = posts.find(function (p) { return p.is_featured == 1; }) || posts[0];
        container.innerHTML = renderFeaturedPost(featured);
      } else {
        container.innerHTML = posts.map(renderBlogCard).join('');
      }
    })
    .catch(function () {
      container.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;">Failed to load posts.</div>';
    });
}

function renderFeaturedPost(post) {
  var img = post.featured_img
    ? '<img src="' + post.featured_img + '" alt="' + escapeHtml(post.title) + '">'
    : '<div style="background:#fff8e1;width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#f4d000" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg></div>';
  var cat = post.category_name
    ? '<span class="blog-card-category" style="background:' + (post.category_color || '#184a96') + '20;color:' + (post.category_color || '#184a96') + ';">⭐ Featured · ' + escapeHtml(post.category_name) + '</span>'
    : '';
  return (
    '<a href="/blog-post/' + encodeURIComponent(post.slug) + '" class="blog-featured-card">' +
    '<div class="blog-featured-img">' + img + '</div>' +
    '<div class="blog-featured-body">' +
    cat +
    '<h2>' + escapeHtml(post.title) + '</h2>' +
    '<p>' + escapeHtml(post.excerpt) + '</p>' +
    '<div class="blog-meta">' +
    (post.published_at ? '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' + formatDate(post.published_at) + '</span>' : '') +
    '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' + post.read_minutes + ' min read</span>' +
    '</div></div></a>'
  );
}

function renderBlogCard(post) {
  var img = post.featured_img
    ? '<img src="' + post.featured_img + '" alt="' + escapeHtml(post.title) + '">'
    : '<div style="background:#fff8e1;width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f4d000" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg></div>';
  var cat = post.category_name
    ? '<span class="blog-card-category" style="background:' + (post.category_color || '#184a96') + '20;color:' + (post.category_color || '#184a96') + ';">' + escapeHtml(post.category_name) + '</span>'
    : '';
  return (
    '<a href="/blog-post/' + encodeURIComponent(post.slug) + '" class="blog-card">' +
    '<div class="blog-card-img">' + img + '</div>' +
    '<div class="blog-card-body">' +
    cat +
    '<h3>' + escapeHtml(post.title) + '</h3>' +
    '<p>' + escapeHtml(post.excerpt) + '</p>' +
    '<div class="blog-meta">' +
    (post.published_at ? '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' + formatDate(post.published_at) + '</span>' : '') +
    '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' + post.read_minutes + ' min</span>' +
    '</div></div></a>'
  );
}

function formatDate(dateStr) {
  var d = new Date(dateStr);
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
}

// ====== Blog post detail loader ======
function loadBlogPost() {
  var params = new URLSearchParams(window.location.search);
  var slug = params.get('slug');
  // Pretty URL support: /blog-post/my-slug
  if (!slug) {
    var path = window.location.pathname.replace(/\/+$/, '');
    var m = path.match(/\/blog-post\/([^\/]+)$/);
    if (m) slug = decodeURIComponent(m[1]);
  }
  if (!slug) return;

  fetch('/api/get-blog-post.php?slug=' + encodeURIComponent(slug))
    .then(function (res) {
      if (!res.ok) throw new Error('Not found');
      return res.json();
    })
    .then(function (data) {
      var post = data.item;
      if (!post) throw new Error('Not found');

      document.title = post.title + ' | Cocohouse Blog';
      document.getElementById('blog-title').textContent = post.title;
      document.getElementById('blog-excerpt').textContent = post.excerpt;
      if (post.published_at) document.getElementById('blog-date').textContent = formatDate(post.published_at);
      document.getElementById('blog-read-time').textContent = post.read_minutes + ' min read';

      if (post.category_name) {
        var catEl = document.getElementById('blog-category');
        catEl.textContent = post.category_name;
        catEl.style.color = post.category_color || '#184a96';
      }

      var imgEl = document.getElementById('blog-image');
      if (post.featured_img) {
        imgEl.innerHTML = '<img src="' + post.featured_img + '" alt="' + escapeHtml(post.title) + '">';
      } else {
        imgEl.style.display = 'none';
      }

      document.getElementById('blog-content').innerHTML = post.content;

      // Tags
      if (post.tags && post.tags.length > 0) {
        var tagsHtml = post.tags.map(function (t) {
          return '<span style="font-size:0.75rem;font-weight:600;padding:4px 12px;border-radius:999px;background:#f3f4f6;color:#4b5563;">' + escapeHtml(t.name) + '</span>';
        }).join('');
        document.getElementById('blog-tags').innerHTML = tagsHtml;
      }

      // Related
      if (post.related && post.related.length > 0) {
        var relHtml = post.related.map(renderBlogCard).join('');
        document.getElementById('blog-related').innerHTML = relHtml;
      } else {
        document.getElementById('blog-related-section').style.display = 'none';
      }
    })
    .catch(function () {
      document.getElementById('blog-content').innerHTML =
        '<div style="text-align:center;padding:60px;color:#9ca3af;"><h2>Post not found</h2><p style="margin-top:12px;"><a href="/blog" style="color:#184a96;text-decoration:underline;">← Back to Blog</a></p></div>';
    });
}
