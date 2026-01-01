class LanguageTranslator {
    constructor() {
        this.currentLang = localStorage.getItem('preferredLanguage') || 'id';
        this.supportedLanguages = {
            'id': { name: 'Bahasa Indonesia', flag: '🇮🇩' },
            'en': { name: 'English', flag: '🇺🇸' },
            'ja': { name: '日本語', flag: '🇯🇵' },
            'ko': { name: '한국어', flag: '🇰🇷' },
            'zh': { name: '中文', flag: '🇨🇳' },
            'es': { name: 'Español', flag: '🇪🇸' },
            'fr': { name: 'Français', flag: '🇫🇷' },
            'de': { name: 'Deutsch', flag: '🇩🇪' }
        };
        this.translationCache = new Map();
        this.isTranslating = false;
        this.translatedPages = new Set();
        this.translatorEnabled = true;
        this.excludedWords = [
            'Tehe-tehe', 'Amplang', 'Kuku Macan', 'Kima-Kima',
            'Berau', 'Derawan', 'Maratua', 'Kakaban', 'Sangalaki',
        ];
        this.customTranslations = {
            'Kuliner & UMKM': {
                'en': 'Food & Delicacies',
                'ja': '料理と名物',
                'ko': '음식 & 별미',
                'zh': '美食与特产',
                'es': 'Comida y Delicias',
                'fr': 'Cuisine et Délices',
                'de': 'Essen & Köstlichkeiten'
            }
        };
        this.loadCacheFromStorage();
        this.checkTranslatorStatus();
    }

    async checkTranslatorStatus() {
        try {
            const response = await fetch('api/translate.php?check_status=1');
            const data = await response.json();
            this.translatorEnabled = data.enabled;
            
            if (!this.translatorEnabled) {
                this.currentLang = 'id';
                localStorage.setItem('preferredLanguage', 'id');
                
                this.resetToOriginal();
                
                console.log('Translator disabled - reset to Indonesian');
            } else {
                if (this.currentLang !== 'id') {
                    setTimeout(() => this.translatePage(this.currentLang), 500);
                }
            }
        } catch (error) {
            console.error('Failed to check translator status:', error);
            this.translatorEnabled = true;
            this.init();
        }
    }

    init() {
        if (this.currentLang !== 'id') {
            setTimeout(() => this.translatePage(this.currentLang), 500);
        }
    }

    loadCacheFromStorage() {
        try {
            const savedCache = localStorage.getItem('translationCache');
            if (savedCache) {
                const cacheData = JSON.parse(savedCache);
                this.translationCache = new Map(Object.entries(cacheData));
            }
        } catch (e) {
            console.error('Failed to load cache:', e);
        }
    }

    saveCacheToStorage() {
        try {
            const cacheData = Object.fromEntries(this.translationCache);
            localStorage.setItem('translationCache', JSON.stringify(cacheData));
        } catch (e) {
            console.error('Failed to save cache:', e);
        }
    }

    async translateText(text, targetLang, sourceLang = 'id') {
        if (!this.translatorEnabled) {
            return text;
        }
        
        if (!text || !text.trim()) {
            return text;
        }

        const trimmedText = text.trim();

        if (this.customTranslations[trimmedText] && this.customTranslations[trimmedText][targetLang]) {
            return this.customTranslations[trimmedText][targetLang];
        }

        const isExcluded = this.excludedWords.some(word => 
            trimmedText.toLowerCase() === word.toLowerCase()
        );
        if (isExcluded) {
            return text;
        }

        const cacheKey = `${text}_${targetLang}`;
        if (this.translationCache.has(cacheKey)) {
            return this.translationCache.get(cacheKey);
        }

        try {
            const response = await fetch('api/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    text: text,
                    target_lang: targetLang,
                    source_lang: sourceLang
                })
            });

            if (!response.ok) {
                console.error('HTTP error:', response.status, response.statusText);
                return text;
            }

            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response text:', responseText.substring(0, 200));
                return text;
            }

            if (data.success) {
                this.translationCache.set(cacheKey, data.translated_text);
                this.saveCacheToStorage();
                return data.translated_text;
            } else {
                if (data.disabled) {
                    this.translatorEnabled = false;
                    console.warn('Fitur penerjemah dinonaktifkan oleh administrator');
                    return text;
                }
                console.error('Translation error:', data.error);
                return text;
            }
        } catch (error) {
            console.error('Fetch error:', error);
            return text;
        }
    }

    async translatePage(targetLang) {
        if (!this.translatorEnabled) {
            this.resetToOriginal();
            this.currentLang = 'id';
            localStorage.setItem('preferredLanguage', 'id');
            
            this.showDisabledNotification();
            return;
        }
        
        if (this.isTranslating) {
            return;
        }

        if (targetLang === 'id') {
            this.applyLanguage('id');
            this.currentLang = 'id';
            localStorage.setItem('preferredLanguage', 'id');
            return;
        }

        if (this.translatedPages.has(targetLang)) {
            this.applyLanguage(targetLang);
            this.currentLang = targetLang;
            localStorage.setItem('preferredLanguage', targetLang);
            return;
        }

        this.isTranslating = true;
        this.showLoadingIndicator();

        try {
            const selectors = [
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p:not(.no-translate)', 
                'a:not(.language-btn):not(.language-option)', 
                'span:not(.benefit-icon):not(.current-flag):not(.flag):not(.lang-name)', 
                'label', 'li',
                'button:not(.language-btn):not(.language-option):not(#darkModeToggle):not(.search-btn)',
                '.card-content h3', '.card-content p',
                '.card-body', '.card-text',
                '.card-description', '.card-title', '.card-subtitle',
                '.timeline-desc', '.section-title', 
                '.hero-content p',
                '.btn-primary', '.btn-secondary', '.btn-timeline',
                '.btn-feedback-primary',
                '.nav-link', '.tab-link',
                'input[placeholder]', 'textarea[placeholder]',
                '.search-input',
                '.feedback-body h2', '.feedback-body p',
                '.benefit-item span:not(.benefit-icon)',
                'th', 'td', 'caption',
                'footer p', 'footer a:not(.social-link)', 'footer span',
                '.footer-text', '.footer-link', '.footer-description',
                '.contact-info p', '.copyright'
            ];

            const elements = document.querySelectorAll(selectors.join(','));
            const translationPromises = [];
            let processedCount = 0;
            let skippedCount = 0;

            for (const element of elements) {
                if (element.closest('.language-dropdown')) {
                    skippedCount++;
                    continue;
                }
                
                if (element.classList.contains('nav-logo')) {
                    skippedCount++;
                    continue;
                }
                
                if (element.id === 'darkModeToggle' || element.classList.contains('search-btn') 
                    || element.classList.contains('dark-mode-toggle')) {
                    skippedCount++;
                    continue;
                }

                if (element.dataset.translated === 'true' && element.dataset[`translated_${targetLang}`]) {
                    continue;
                }
                
                if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                    if (!element.dataset.originalText) {
                        element.dataset.originalText = element.getAttribute('placeholder') || '';
                    }
                    
                    const originalText = element.dataset.originalText;
                    if (originalText && originalText.trim()) {
                        translationPromises.push(
                            this.translateText(originalText, targetLang)
                                .then(translated => {
                                    element.setAttribute('placeholder', translated);
                                    element.dataset.translated = 'true';
                                    element.dataset[`translated_${targetLang}`] = translated;
                                    processedCount++;
                                })
                                .catch(error => {
                                    console.error(`Translation failed for placeholder: ${originalText}`, error);
                                })
                        );
                    }
                } else {
                    if (!element.dataset.originalText) {
                        let textContent = '';
                        for (let node of element.childNodes) {
                            if (node.nodeType === Node.TEXT_NODE) {
                                textContent += node.textContent;
                            }
                        }
                        textContent = textContent.trim();
                        
                        if (!textContent) {
                            textContent = element.textContent.trim();
                        }
                        
                        if (textContent.length < 2) {
                            skippedCount++;
                            continue;
                        }
                        element.dataset.originalText = textContent;
                    }
                    
                    const originalText = element.dataset.originalText;
                    
                    if (!originalText || originalText.length < 2) {
                        skippedCount++;
                        continue;
                    }

                    const hasComplexChildren = Array.from(element.children).some(child => 
                        !['I', 'SPAN', 'B', 'STRONG', 'EM', 'SMALL'].includes(child.tagName)
                    );
                    
                    if (hasComplexChildren) {
                        skippedCount++;
                        continue;
                    }

                    translationPromises.push(
                        this.translateText(originalText, targetLang)
                            .then(translated => {
                                if (element.children.length > 0) {
                                    for (let node of element.childNodes) {
                                        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                                            node.textContent = translated;
                                            break;
                                        }
                                    }
                                } else {
                                    element.textContent = translated;
                                }
                                element.dataset.translated = 'true';
                                element.dataset[`translated_${targetLang}`] = translated;
                                processedCount++;
                            })
                            .catch(error => {
                                console.error(`Translation failed for: ${originalText.substring(0, 50)}...`, error);
                            })
                    );
                }

                if (translationPromises.length >= 15) {
                    await Promise.all(translationPromises);
                    translationPromises.length = 0;
                    await this.delay(300);
                }
            }

            if (translationPromises.length > 0) {
                await Promise.all(translationPromises);
            }

            console.log(`✅ Translation complete for ${targetLang}:`);
            console.log(`   - ${processedCount} elements translated`);
            console.log(`   - ${skippedCount} elements skipped`);
            console.log(`   - Total elements checked: ${elements.length}`);



            this.translatedPages.add(targetLang);
            this.currentLang = targetLang;
            localStorage.setItem('preferredLanguage', targetLang);

        } catch (error) {
            console.error('Translation error:', error);
            alert('Terjadi kesalahan saat menerjemahkan halaman. Silakan coba lagi.');
        } finally {
            this.isTranslating = false;
            this.hideLoadingIndicator();
        }
    }

    showLoadingIndicator() {
        let loader = document.getElementById('translation-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'translation-loader';
            loader.className = 'translation-loader';
            loader.innerHTML = `
                <div class="loader-content">
                    <div class="spinner"></div>
                    <p>Lagi ganti bahasa, tunggu dulu ya...</p>
                </div>
            `;
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    }

    hideLoadingIndicator() {
        const loader = document.getElementById('translation-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    showDisabledNotification() {
        const notification = document.createElement('div');
        notification.className = 'translator-disabled-notification';
        
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#1976d2" stroke-width="2" fill="none"/>
                        <path d="M12 8V12" stroke="#1976d2" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="16" r="1" fill="#1976d2"/>
                    </svg>
                </div>
                <div class="notification-text">
                    <strong>Fitur Tidak Tersedia</strong>
                    <p>Fitur ganti bahasa sedang dinonaktifkan</p>
                </div>
                <button class="notification-close" onclick="this.parentElement.parentElement.classList.add('hiding'); setTimeout(() => this.parentElement.parentElement.remove(), 400)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('hiding');
            setTimeout(() => {
                notification.remove();
            }, 400);
        }, 4000);
    }

    resetToOriginal() {
        const translatedElements = document.querySelectorAll('[data-original-text]');
        
        translatedElements.forEach(element => {
            const originalText = element.dataset.originalText;
            
            if (element.tagName === 'INPUT') {
                element.setAttribute('placeholder', originalText);
            } else {
                element.textContent = originalText;
            }
            
            element.dataset.translated = 'false';
        });
    }

    applyLanguage(targetLang) {
        const translatedElements = document.querySelectorAll('[data-original-text]');
        
        translatedElements.forEach(element => {
            if (targetLang === 'id') {
                const originalText = element.dataset.originalText;
                if (element.tagName === 'INPUT') {
                    element.setAttribute('placeholder', originalText);
                } else {
                    element.textContent = originalText;
                }
            } else {
                const cachedTranslation = element.dataset[`translated_${targetLang}`];
                if (cachedTranslation) {
                    if (element.tagName === 'INPUT') {
                        element.setAttribute('placeholder', cachedTranslation);
                    } else {
                        element.textContent = cachedTranslation;
                    }
                }
            }
        });
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    getSupportedLanguages() {
        return this.supportedLanguages;
    }

    getCurrentLanguage() {
        return this.currentLang;
    }
    
    isEnabled() {
        return this.translatorEnabled;
    }
}

const translator = new LanguageTranslator();

function createLanguageSwitcher() {
    const navActions = document.querySelector('.nav-actions');
    if (!navActions) return;

    if (!translator.isEnabled()) {
        console.log('Translator disabled, language switcher not created');
        return;
    }

    const languageSelector = document.createElement('div');
    languageSelector.className = 'language-selector';
    
    const currentLang = translator.getCurrentLanguage();
    const languages = translator.getSupportedLanguages();
    
    languageSelector.innerHTML = `
        <button class="language-btn" aria-label="Change language">
            <span class="current-flag">${languages[currentLang].flag}</span>
            <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="language-dropdown">
            ${Object.entries(languages).map(([code, lang]) => `
                <button class="language-option ${code === currentLang ? 'active' : ''}" data-lang="${code}">
                    <span class="flag">${lang.flag}</span>
                    <span class="lang-name">${lang.name}</span>
                </button>
            `).join('')}
        </div>
    `;

    const darkModeToggle = navActions.querySelector('#darkModeToggle');
    navActions.insertBefore(languageSelector, darkModeToggle);

    const langBtn = languageSelector.querySelector('.language-btn');
    const langDropdown = languageSelector.querySelector('.language-dropdown');
    const langOptions = languageSelector.querySelectorAll('.language-option');

    langBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        langDropdown.classList.toggle('show');
    });

    langOptions.forEach(option => {
        option.addEventListener('click', async (e) => {
            e.stopPropagation();
            const selectedLang = option.dataset.lang;
            
            if (selectedLang !== translator.getCurrentLanguage()) {
                document.querySelectorAll('.language-option').forEach(opt => 
                    opt.classList.remove('active')
                );
                option.classList.add('active');
                
                const currentFlag = languageSelector.querySelector('.current-flag');
                currentFlag.textContent = languages[selectedLang].flag;
                
                langDropdown.classList.remove('show');

                await translator.translatePage(selectedLang);
            } else {
                langDropdown.classList.remove('show');
            }
        });
    });

    document.addEventListener('click', () => {
        langDropdown.classList.remove('show');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createLanguageSwitcher);
} else {
    createLanguageSwitcher();
}