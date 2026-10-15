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

// ====== Footer lists on phone ======
(function () {
  document.querySelectorAll('.footer h4').forEach(function (heading) {
    var label = heading.textContent.trim();
    if (label !== 'Quick Links' && label !== 'Our Programs') return;
    var box = heading.parentElement;
    box.classList.add('footer-fold');
    heading.setAttribute('role', 'button');
    heading.setAttribute('tabindex', '0');
    heading.setAttribute('aria-expanded', 'false');
    function toggle() {
      var open = box.classList.toggle('open');
      heading.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    heading.addEventListener('click', toggle);
    heading.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggle();
      }
    });
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

function cocoEvent(name, params) {
  params = params || {};
  if (!params.page_path) params.page_path = window.location.pathname;
  var payload = { event: name };
  Object.keys(params).forEach(function (key) {
    if (params[key] !== '' && params[key] != null) payload[key] = params[key];
  });
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
  if (typeof gtag === 'function') gtag('event', name, params);
}

function cocoLinkPlace(link) {
  if (link.classList.contains('whatsapp-float')) return 'sticky';
  if (link.closest('.coco-popup')) return 'popup';
  if (link.closest('.footer')) return 'footer';
  if (link.closest('.navbar, .mobile-drawer')) return 'menu';
  if (link.closest('.contact-ways, .contact-fresh')) return 'contact';
  if (link.closest('.hero, .page-hero, .hero-content')) return 'hero';
  if (link.closest('form')) return 'form';
  return 'page';
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
    var adParams = new URLSearchParams(window.location.search);
    ['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function (key) {
      var value = adParams.get(key);
      if (value) data[key] = value.slice(0, 180);
    });
    data.landing = window.location.pathname;

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
      home_hero: 'Home Hero Section Form',
      admission_page: 'Admission Page Form',
      popup: 'Popup Form',
      school_visit: 'School Visit Footer Form',
      daycare: 'Daycare Page Form',
      contact: 'Contact Page Form',
      special_education: 'Special Ed Form'
    };
    var lines = ['Hello Cocohouse Preschool,'];
    lines.push('Enquiry: ' + (typeLabels[options.type] || 'Website Enquiry'));
    lines.push('Source: ' + (new URLSearchParams(window.location.search).get('utm_source') || 'website'));
    var skipKeys = { type: 1, company: 1, gclid: 1, gbraid: 1, wbraid: 1, utm_source: 1, utm_medium: 1, utm_campaign: 1, utm_term: 1, utm_content: 1, landing: 1 };
    Object.keys(data).forEach(function (key) {
      if (!data[key] || skipKeys[key]) return;
      lines.push((labels[key] || key) + ': ' + String(data[key]).trim());
    });

    var submitBtn = form.querySelector('button[type="submit"]');
    var originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Submitting...';
    }

    fetch(apiEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
      .then(function (res) {
        return res.json().then(function (body) {
          return { ok: res.ok, body: body };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.body || !result.body.success) {
          var message = (result.body && result.body.error) || 'Please try again.';
          showToast('Could not submit', message, 'error');
          return;
        }
        showToast(
          options.successTitle || 'Submitted!',
          options.successDesc || 'Our team will contact you within 24 hours.'
        );
        var programField = form.querySelector('[name="program"]');
        var program = programField ? String(programField.value || '').trim().slice(0, 80) : '';
        form.reset();
        cocoEvent('generate_lead', {
          form_name: typeLabels[options.type] || 'Website Enquiry',
          form_id: options.type || 'contact',
          program: program
        });
        if (typeof options.onSuccess === 'function') options.onSuccess();
      })
      .catch(function () {
        var wa = 'https://wa.me/917039152173?text=' + encodeURIComponent(lines.join('\n'));
        window.open(wa, '_blank', 'noopener');
        cocoEvent('whatsapp_click', { link_place: 'form_fallback', form_id: options.type || 'contact' });
        showToast(
          options.successTitle || 'Submitted!',
          'WhatsApp is opening so you can send this enquiry to our team.'
        );
        form.reset();
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
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
    (post.author ? '<span>' + escapeHtml(post.author) + '</span>' : '') +
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
    (post.author ? '<span>' + escapeHtml(post.author) + '</span>' : '') +
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

      document.title = post.title + ' | Cocohouse Blogs';
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
        '<div style="text-align:center;padding:60px;color:#9ca3af;"><h2>Post not found</h2><p style="margin-top:12px;"><a href="/blog" style="color:#184a96;text-decoration:underline;">← Back to Blogs</a></p></div>';
    });
}

var COCO_MAPS = 'https://share.google/HHjCBetZvLFN4tJBN';
var COCO_WA_ICON = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>';

function cocoIncoming() {
  return new URLSearchParams(window.location.search);
}

function cocoUtm(content) {
  var incoming = cocoIncoming();
  var query = new URLSearchParams();
  ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'gclid', 'gbraid', 'wbraid'].forEach(function (key) {
    var value = incoming.get(key);
    if (value) query.set(key, value);
  });
  if (!query.get('utm_source')) query.set('utm_source', 'website');
  if (!query.get('utm_medium')) query.set('utm_medium', 'button');
  if (content) query.set('utm_content', content);
  return query;
}

function cocoButtonName(link) {
  var name = link.getAttribute('data-utm') || link.getAttribute('aria-label') || link.textContent || 'button';
  name = String(name).trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  return name || 'button';
}

function cocoWaMessage(content) {
  var query = cocoUtm(content);
  var lines = ['Hello Cocohouse, I want admission details for 2026-27.'];
  lines.push('Source: ' + query.get('utm_source'));
  if (query.get('utm_campaign')) lines.push('Campaign: ' + query.get('utm_campaign'));
  if (content) lines.push('Button: ' + content);
  return lines.join('\n');
}

function cocoWaLink(content) {
  return 'https://wa.me/917039152173?text=' + encodeURIComponent(cocoWaMessage(content));
}

function cocoWithUtm(href, content) {
  if (!href || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0 || href.indexOf('javascript:') === 0) return href;
  if (href.indexOf('wa.me') !== -1) return cocoWaLink(content);
  if (href.indexOf('share.google') !== -1) return href;
  var hash = '';
  var hashAt = href.indexOf('#');
  if (hashAt !== -1) {
    hash = href.slice(hashAt);
    href = href.slice(0, hashAt);
  }
  var base = href;
  var existing = '';
  var queryAt = href.indexOf('?');
  if (queryAt !== -1) {
    base = href.slice(0, queryAt);
    existing = href.slice(queryAt + 1);
  }
  if (!base) base = window.location.pathname;
  var merged = new URLSearchParams(existing);
  cocoUtm(content).forEach(function (value, key) { merged.set(key, value); });
  return base + '?' + merged.toString() + hash;
}

function cocoTrackLinks() {
  document.querySelectorAll('.whatsapp-float').forEach(function (link) {
    link.innerHTML = COCO_WA_ICON;
    link.href = cocoWaLink('whatsapp-sticky');
  });

  document.querySelectorAll('a.btn, a.nav-cta, .ads-bar a, a[href*="wa.me"]').forEach(function (link) {
    if (link.classList.contains('whatsapp-float')) return;
    var content = cocoButtonName(link);
    var href = link.getAttribute('href') || '';
    if (href.indexOf('wa.me') !== -1) {
      link.href = cocoWaLink(content);
      return;
    }
    link.href = cocoWithUtm(href, content);
  });

  var addressNodes = [];
  var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  while (walker.nextNode()) {
    if (walker.currentNode.nodeValue.indexOf('Millenium Park') !== -1) addressNodes.push(walker.currentNode);
  }
  addressNodes.forEach(function (node) {
    var parent = node.parentElement;
    if (!parent || parent.closest('a')) return;
    var text = node.nodeValue;
    var start = text.indexOf('Row House No 3');
    if (start === -1) start = text.indexOf('Millenium Park');
    if (start === -1) return;
    var tail = text.slice(start);
    var endMatch = tail.match(/Mumbai\s*-\s*400081/);
    if (!endMatch) return;
    var end = start + endMatch.index + endMatch[0].length;
    var link = document.createElement('a');
    link.href = COCO_MAPS;
    link.target = '_blank';
    link.rel = 'noopener';
    link.style.color = 'inherit';
    link.style.textDecoration = 'underline';
    link.textContent = text.slice(start, end);
    var fragment = document.createDocumentFragment();
    if (start > 0) fragment.appendChild(document.createTextNode(text.slice(0, start)));
    fragment.appendChild(link);
    if (end < text.length) fragment.appendChild(document.createTextNode(text.slice(end)));
    node.parentNode.replaceChild(fragment, node);
  });

  document.querySelectorAll('iframe[src*="google.com/maps"], iframe[src*="maps.google"]').forEach(function (frame) {
    var box = frame.parentElement;
    if (!box || box.querySelector('.coco-map-link')) return;
    box.style.position = 'relative';
    var link = document.createElement('a');
    link.className = 'coco-map-link';
    link.href = COCO_MAPS;
    link.target = '_blank';
    link.rel = 'noopener';
    link.setAttribute('aria-label', 'Open Cocohouse on Google Maps');
    link.style.cssText = 'position:absolute;inset:0;z-index:2;';
    box.appendChild(link);
  });
}

function initBlogView() {
  var box = document.getElementById('blogPosts');
  var bar = document.querySelector('.blog-view');
  if (!box || !bar) return;
  bar.addEventListener('click', function (event) {
    var button = event.target.closest('button[data-view]');
    if (!button) return;
    var list = button.getAttribute('data-view') === 'list';
    box.classList.toggle('is-list', list);
    bar.querySelectorAll('button').forEach(function (item) {
      item.classList.toggle('active', item === button);
    });
  });
}

function initFlipCards() {
  document.querySelectorAll('.se-type').forEach(function (card) {
    card.addEventListener('click', function () {
      card.classList.toggle('is-flipped');
    });
    card.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      card.classList.toggle('is-flipped');
    });
  });
}

function cocoTrackClicks() {
  document.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link) return;
    var href = link.getAttribute('href') || '';
    var place = cocoLinkPlace(link);
    if (href.indexOf('tel:') === 0) {
      cocoEvent('phone_click', { link_place: place });
      return;
    }
    if (href.indexOf('mailto:') === 0) {
      cocoEvent('email_click', { link_place: place });
      return;
    }
    if (href.indexOf('wa.me') !== -1 || href.indexOf('api.whatsapp.com') !== -1) {
      cocoEvent('whatsapp_click', { link_place: place });
      return;
    }
    if (link.classList.contains('coco-map-link') || href.indexOf('share.google') !== -1 || href.indexOf('google.com/maps') !== -1 || href.indexOf('maps.google') !== -1) {
      cocoEvent('directions_click', { link_place: place });
      return;
    }
    if (href.indexOf('adminssion-open-2026') !== -1) {
      cocoEvent('admission_click', { link_place: place });
    }
  });
}

function initPageExtras() {
  cocoTrackLinks();
  cocoTrackClicks();
  initBlogView();
  initFlipCards();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPageExtras);
} else {
  initPageExtras();
}
