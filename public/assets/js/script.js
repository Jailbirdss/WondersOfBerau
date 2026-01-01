const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-menu");
const navbar = document.querySelector(".navbar");
const searchForm = document.querySelector(".search-form");
const searchInput = document.querySelector(".search-input");
const searchBtn = document.querySelector(".search-btn");
let searchOverlay;
let searchOverlayForm;
let searchOverlayInput;
let closeSearchOverlay = () => {};

if (hamburger) {
    hamburger.addEventListener("click", () => {
        closeSearchOverlay();
        hamburger.classList.toggle("active");
        navMenu.classList.toggle("active");
    });
}

document.querySelectorAll(".nav-link").forEach(n => n.addEventListener("click", () => {
    if (hamburger) {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
    }
    closeSearchOverlay();
}));

if (navbar) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

const applySearchCompactMode = () => {
    if (!searchForm || !searchInput || !searchBtn) return;
    const useCompact = window.innerWidth <= 1000;
    searchForm.classList.toggle('compact-search', useCompact);
    if (!useCompact) {
        searchForm.classList.remove('expanded');
        searchInput.required = true;
        searchInput.style.opacity = '';
        searchInput.style.width = '';
        if (searchOverlay) {
            searchOverlay.classList.remove('active');
        }
        return;
    }
    searchForm.classList.remove('expanded');
    searchInput.required = false;
};

applySearchCompactMode();
window.addEventListener('resize', applySearchCompactMode);

const setNavHeightVar = () => {
    if (!navbar) return;
    document.documentElement.style.setProperty('--nav-height', `${navbar.offsetHeight}px`);
};
setNavHeightVar();
window.addEventListener('resize', setNavHeightVar);

const createSearchOverlay = () => {
    if (!searchForm) return null;
    const overlay = document.createElement('div');
    overlay.className = 'search-overlay';
    const action = searchForm.getAttribute('action') || 'search.php';
    const method = searchForm.getAttribute('method') || 'GET';

    overlay.innerHTML = `
        <div class="search-overlay-backdrop"></div>
        <div class="search-overlay-panel">
            <div class="search-overlay-box">
                <form class="search-overlay-form" action="${action}" method="${method}">
                    <input type="text" name="q" class="search-overlay-input" placeholder="${searchInput ? searchInput.placeholder : 'Cari destinasi, kuliner, event...'}" required />
                    <button type="submit" class="search-overlay-submit">Cari</button>
                    <button type="button" class="search-overlay-close" aria-label="Tutup pencarian">&times;</button>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    searchOverlayForm = overlay.querySelector('.search-overlay-form');
    searchOverlayInput = overlay.querySelector('.search-overlay-input');
    const closeBtn = overlay.querySelector('.search-overlay-close');
    const backdrop = overlay.querySelector('.search-overlay-backdrop');

    const closeOverlay = () => {
        overlay.classList.remove('active');
        if (searchInput) searchInput.required = false;
    };
    closeSearchOverlay = closeOverlay;

    closeBtn?.addEventListener('click', closeOverlay);
    backdrop?.addEventListener('click', closeOverlay);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeOverlay();
        }
    });

    searchOverlayForm?.addEventListener('submit', () => {
        if (searchOverlayInput && searchInput) {
            searchInput.value = searchOverlayInput.value;
            searchInput.required = true;
        }
    });

    return overlay;
};

searchOverlay = createSearchOverlay();

const openSearchOverlay = () => {
    if (!searchOverlay || !searchOverlayInput) return;
    setNavHeightVar();
    searchOverlay.classList.add('active');
    if (searchInput) {
        searchOverlayInput.value = searchInput.value || '';
    }
    setTimeout(() => searchOverlayInput.focus(), 10);
};

if (searchForm && searchBtn && searchInput) {
    searchBtn.addEventListener('click', (e) => {
        if (searchForm.classList.contains('compact-search')) {
            e.preventDefault();
            openSearchOverlay();
        }
    });
}

function openTab(evt, tabName) {
    let tabcontent = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }

    let tablinks = document.getElementsByClassName("tab-link");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    const currentTab = document.getElementById(tabName);
    if (currentTab) {
        currentTab.style.display = "block";
        currentTab.classList.add("active");
        evt.currentTarget.className += " active";
        
        moveSlider(evt.currentTarget);
    }
}

(function () {
    const btnTop = document.createElement('button');
    btnTop.className = 'back-to-top';
    btnTop.setAttribute('aria-label', 'Kembali ke atas');
    btnTop.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 19V5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12L12 5L19 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    document.body.appendChild(btnTop);

    const btnDown = document.createElement('button');
    btnDown.className = 'back-to-down';
    btnDown.setAttribute('aria-label', 'Ke bawah halaman');
    btnDown.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12l7 7 7-7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    document.body.appendChild(btnDown);

    const reportBtn = document.createElement('button');
    reportBtn.className = 'report-fab';
    reportBtn.setAttribute('aria-label', 'Kirim laporan/komplain');
    reportBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 4L3 10.5L9.5 13.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.5 20L20 4L9.5 13.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 13.5V17.5L12.2 14.7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class=\"report-label\">Laporkan</span>';
    reportBtn.addEventListener('click', () => {
        window.location.href = 'laporan.php';
    });
    document.body.appendChild(reportBtn);

    const heroSection = document.querySelector('.hero');
    let heroHeight = heroSection ? heroSection.offsetHeight : 0;

    const updateHeroHeight = () => {
        heroHeight = heroSection ? heroSection.offsetHeight : 0;
    };

    const updateFabOffset = () => {
        const base = 22;
        const safety = 16; // keep a small gap before hitting footer text/links
        const footerBottom = document.querySelector('.footer-bottom');
        const target = footerBottom || document.querySelector('footer');
        let offset = base;
        if (target) {
            const rect = target.getBoundingClientRect();
            const overlap = window.innerHeight - (rect.top - safety);
            if (overlap > 0) {
                offset = base + overlap;
            }
        }
        document.documentElement.style.setProperty('--fab-offset', `${offset}px`);
    };

    const toggleVisibility = () => {
        const visible = window.scrollY > 180;
        const footerEl = document.querySelector('footer');
        const footerTop = footerEl ? footerEl.getBoundingClientRect().top : Infinity;
        const nearBottom = footerTop <= window.innerHeight; // sembunyikan sebelum footer masuk viewport
        const pageNameFab = (location.pathname.split('/').pop() || '').toLowerCase();
        const isHomeFab = pageNameFab === '' || pageNameFab === 'index.php';
        const heroThreshold = heroHeight ? heroHeight * 0.2 : 100; // home: segera setelah keluar hero
        const passedHero = isHomeFab ? (window.scrollY >= heroThreshold) : visible; // non-home: pakai trigger biasa
        btnTop.classList.toggle('visible', visible);
        btnDown.classList.toggle('visible', !nearBottom && passedHero);
        reportBtn.classList.toggle('visible', visible);
        updateFabOffset();
    };

    btnTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    btnDown.addEventListener('click', () => {
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    });

    window.addEventListener('scroll', toggleVisibility);
    window.addEventListener('resize', updateFabOffset);
    window.addEventListener('resize', updateHeroHeight);
    updateFabOffset();
    updateHeroHeight();
    toggleVisibility();
})();

function moveSlider(activeTab) {
    const tabsContainer = activeTab.parentElement;
    const slider = tabsContainer.querySelector('::after') || tabsContainer;
    
    if (slider && activeTab) {
        const tabRect = activeTab.getBoundingClientRect();
        const containerRect = tabsContainer.getBoundingClientRect();
        const left = tabRect.left - containerRect.left;
        const width = tabRect.width;
        
        tabsContainer.style.setProperty('--slider-width', width + 'px');
        tabsContainer.style.setProperty('--slider-left', left + 'px');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const firstTab = document.querySelector(".tab-link");
    if (firstTab) {
        firstTab.click();
        setTimeout(() => moveSlider(firstTab), 100);
    }
    if (firstTab) {
        firstTab.click();
    }

    const pageLinks = document.querySelectorAll('a:not([href^="#"]):not([target="_blank"])');
    pageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                e.preventDefault();
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = href;
                }, 400);
            }
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });

    const elementsToAnimate = document.querySelectorAll(
        '.section-title, .page-title, .section-subtitle, .card, .event-item, .timeline-item, .value-card, .stat-item, .gallery-card, .about-highlight-card, .animate-on-scroll'
    );
    elementsToAnimate.forEach(el => {
        const delay = el.dataset.delay;
        if (delay) el.style.transitionDelay = `${delay}ms`;
        observer.observe(el);
    });

    const pageName = (location.pathname.split('/').pop() || '').toLowerCase();
    const isHome = pageName === '' || pageName === 'index.php';
    if (!isHome) {
        const autoAnimTargets = document.querySelectorAll(
            '.section, .card-grid > .card, .faq-item, .info-item, .rekomendasi-card, .tip-card, .map-card, .contact-card, .about-cta, .timeline-card, .event-item'
        );
        autoAnimTargets.forEach((el, idx) => {
            if (!el.classList.contains('animate-on-scroll')) {
                el.classList.add('animate-on-scroll');
                if (!el.dataset.delay) {
                    el.dataset.delay = Math.min(60 * (idx % 5), 180).toString();
                }
                observer.observe(el);
            }
        });
    }

    const faqItems = document.querySelectorAll('.faq-item');
    const setPanelHeight = (panel, expanded) => {
        if (!panel) return;
        panel.style.maxHeight = expanded ? `${panel.scrollHeight}px` : null;
    };

    faqItems.forEach(item => {
        const button = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        if (!button || !answer) return;

        if (item.classList.contains('open')) {
            button.setAttribute('aria-expanded', 'true');
            setPanelHeight(answer, true);
        } else {
            button.setAttribute('aria-expanded', 'false');
        }

        button.addEventListener('click', () => {
            const list = item.parentElement;
            const isOpen = item.classList.contains('open');

            if (list) {
                list.querySelectorAll('.faq-item.open').forEach(other => {
                    if (other === item) return;
                    other.classList.remove('open');
                    const otherBtn = other.querySelector('.faq-question');
                    const otherAns = other.querySelector('.faq-answer');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                    setPanelHeight(otherAns, false);
                });
            }

            item.classList.toggle('open');
            button.setAttribute('aria-expanded', (!isOpen).toString());
            setPanelHeight(answer, !isOpen);
        });
    });

    window.addEventListener('resize', () => {
        document.querySelectorAll('.faq-item.open .faq-answer').forEach(panel => {
            setPanelHeight(panel, true);
        });
    });

    const lazyMaps = document.querySelectorAll('.lazy-map');
    if ('IntersectionObserver' in window) {
        const mapObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    const src = iframe.dataset.src;
                    if (src && !iframe.src) iframe.src = src;
                    obs.unobserve(iframe);
                }
            });
        }, { rootMargin: '200px' });
        lazyMaps.forEach(map => mapObserver.observe(map));
    } else {
        lazyMaps.forEach(map => {
            if (map.dataset.src) map.src = map.dataset.src;
        });
    }

    const currentPage = (location.pathname.split('/').pop() || '').toLowerCase();
    const footerLinks = document.querySelectorAll('.footer-links-list a');
    footerLinks.forEach(link => {
        const href = (link.getAttribute('href') || '').toLowerCase();
        if (href && currentPage === href) {
            link.classList.add('active');
        }
    });

    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    if (statNumbers.length) {
        const easeOut = t => 1 - Math.pow(1 - t, 3);
        const animateCount = (el) => {
            const target = parseInt(el.dataset.target, 10) || 0;
            const suffix = el.dataset.suffix || '';
            const duration = 1200;
            const startTime = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - startTime) / duration, 1);
                const eased = easeOut(progress);
                const value = Math.floor(eased * target);
                el.textContent = `${value}${suffix}`;
                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    el.textContent = `${target}${suffix}`;
                }
            };

            requestAnimationFrame(tick);
        };

        const observerStats = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.counted) {
                    entry.target.dataset.counted = 'true';
                    animateCount(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        statNumbers.forEach(el => {
            el.textContent = `0${el.dataset.suffix || ''}`;
            observerStats.observe(el);
        });
    }
});

(function(){
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  function absURL(relativeOrAbs){
    try {
      return new URL(relativeOrAbs, location.origin).toString();
    } catch { return location.href; }
  }

  function showToast(msg='Link disalin!'){
    const t = $('#toast'); if(!t) return;
    t.textContent = msg;
    t.hidden = false;
    t.classList.remove('show');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(()=>{ t.hidden = true; }, 2500);
  }

  async function handleShare(btn){
    const title = btn.dataset.title || document.title;
    const text  = btn.dataset.text  || '';
    const url   = absURL(btn.dataset.url || location.href);

    if (navigator.share) {
      try { await navigator.share({ title, text, url }); return; }
      catch (e) {}
    }
    try {
      await navigator.clipboard.writeText(url);
      showToast('Link disalin!');
    } catch {
      window.prompt('Salin tautan ini:', url);
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $$('.share-btn').forEach(b=>{
      b.addEventListener('click', ()=>handleShare(b));
    });
  });
})();

(function() {
    const modal = document.getElementById('feedbackModal');
    if (!modal) return;

    const closeBtn = document.getElementById('closeFeedback');
    const fillSurveyBtn = document.getElementById('fillSurvey');

    function closeModal() {
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('show', 'closing');
        }, 300);
    }
    
    setTimeout(() => {
        modal.classList.add('show');
    }, 2000);

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (fillSurveyBtn) {
        fillSurveyBtn.addEventListener('click', function() {
            closeModal();
        });
    }

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
})();

(function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    
    if (isDarkMode) {
        body.classList.add('dark-mode');
    }
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            
            const isDark = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
            
            this.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                this.style.transform = 'rotate(0deg)';
            }, 300);
        });
    }
})();