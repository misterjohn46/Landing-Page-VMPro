/**
 * VMPro Landing Page - Main JavaScript
 * Handlers: Mobile Menu, Tab Showcase, FAQ Accordion, WhatsApp Lead Generator
 */

// Konfigurasi WhatsApp Admin (Otomatis mengambil dari PHP jika ada, atau fallback ke default)
let WHATSAPP_NUMBER = (typeof PHP_WA_NUMBER !== "undefined" && PHP_WA_NUMBER) ? PHP_WA_NUMBER : "6282335338113"; 

document.addEventListener("DOMContentLoaded", function () {
  initMobileMenu();
  initShowcaseTabs();
  initFaqAccordion();
  initEstimator();
  initSmoothScroll();
});

/* =========================================================================
   1. MOBILE MENU TOGGLE
   ========================================================================= */
function initMobileMenu() {
  const menuBtn = document.getElementById("mobile-menu-btn");
  const mobileMenu = document.getElementById("mobile-menu");
  const menuIcon = document.getElementById("menu-icon");

  if (!menuBtn || !mobileMenu) return;

  menuBtn.addEventListener("click", function () {
    const isOpen = !mobileMenu.classList.contains("hidden");
    if (isOpen) {
      mobileMenu.classList.add("hidden");
      menuIcon.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      `;
    } else {
      mobileMenu.classList.remove("hidden");
      menuIcon.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      `;
    }
  });

  // Tutup menu mobile ketika link di-klik
  const mobileLinks = mobileMenu.querySelectorAll("a");
  mobileLinks.forEach((link) => {
    link.addEventListener("click", () => {
      mobileMenu.classList.add("hidden");
      menuIcon.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      `;
    });
  });
}

/* =========================================================================
   2. SHOWCASE TABS (POS vs SISTEM SEKOLAH vs CUSTOM)
   ========================================================================= */
function initShowcaseTabs() {
  const tabBtns = document.querySelectorAll(".showcase-tab-btn");
  const tabContents = document.querySelectorAll(".showcase-content");

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const target = this.getAttribute("data-tab");

      // Active state button
      tabBtns.forEach((b) => {
        b.classList.remove("bg-indigo-600", "text-white", "shadow-lg", "shadow-indigo-500/25");
        b.classList.add("text-slate-400", "hover:text-white", "hover:bg-slate-800");
      });
      this.classList.add("bg-indigo-600", "text-white", "shadow-lg", "shadow-indigo-500/25");
      this.classList.remove("text-slate-400", "hover:text-white", "hover:bg-slate-800");

      // Active content display
      tabContents.forEach((content) => {
        if (content.id === target) {
          content.classList.remove("hidden");
          content.classList.add("animate-fade-in");
        } else {
          content.classList.add("hidden");
        }
      });
    });
  });
}

/* =========================================================================
   3. FAQ ACCORDION
   ========================================================================= */
function initFaqAccordion() {
  const faqItems = document.querySelectorAll(".faq-item");

  faqItems.forEach((item) => {
    const button = item.querySelector(".faq-question");
    const answer = item.querySelector(".faq-answer");
    const icon = item.querySelector(".faq-icon");

    if (!button || !answer) return;

    button.addEventListener("click", () => {
      const isExpanded = !answer.classList.contains("hidden");

      // Tutup semua FAQ lainnya
      faqItems.forEach((otherItem) => {
        const otherAns = otherItem.querySelector(".faq-answer");
        const otherIcon = otherItem.querySelector(".faq-icon");
        if (otherAns) otherAns.classList.add("hidden");
        if (otherIcon) otherIcon.style.transform = "rotate(0deg)";
      });

      if (!isExpanded) {
        answer.classList.remove("hidden");
        if (icon) icon.style.transform = "rotate(180deg)";
      } else {
        answer.classList.add("hidden");
        if (icon) icon.style.transform = "rotate(0deg)";
      }
    });
  });
}

/* =========================================================================
   4. INTERACTIVE PROJECT ESTIMATOR & WHATSAPP GENERATOR
   ========================================================================= */
function initEstimator() {
  const appTypeSelect = document.getElementById("est-app-type");
  const featureListContainer = document.getElementById("est-features-list");
  const form = document.getElementById("lead-form");

  if (!appTypeSelect || !featureListContainer) return;

  const featuresMap = {
    pos: [
      { id: "pos-1", label: "Cetak Struk Bluetooth / Thermal & Dapur", checked: true },
      { id: "pos-2", label: "Barcode Scanner & Manajemen Barcode Produk", checked: true },
      { id: "pos-3", label: "Pembayaran QRIS & Non-Tunai Otomatis", checked: true },
      { id: "pos-4", label: "Multi Cabang / Multi Outlet", checked: false },
      { id: "pos-5", label: "Manajemen Inventori & Notifikasi Stok Menipis", checked: true },
      { id: "pos-6", label: "Laporan Omset, Laba Bersih & Export Excel/PDF", checked: true },
      { id: "pos-7", label: "Hak Akses Kasir, Manajer & Pemilik", checked: true },
    ],
    school: [
      { id: "sch-1", label: "PPDB Online (Pendaftaran Peserta Didik Baru)", checked: true },
      { id: "sch-2", label: "Presensi Guru & Siswa (QR Code / GPS / Fingerprint)", checked: true },
      { id: "sch-3", label: "Pembayaran SPP + Notifikasi WhatsApp Otomatis ke Wali Murid", checked: true },
      { id: "sch-4", label: "e-Rapor Kurikulum Merdeka / K13", checked: true },
      { id: "sch-5", label: "Portal Orang Tua & Siswa (Lihat Nilai & Kehadiran)", checked: false },
      { id: "sch-6", label: "CBT (Computer Based Test / Ujian Online)", checked: false },
      { id: "sch-7", label: "Manajemen Perpustakaan & Inventaris Sekolah", checked: false },
    ],
    custom: [
      { id: "cst-1", label: "Dashboard Admin Berbasis Web Interaktif", checked: true },
      { id: "cst-2", label: "Aplikasi Mobile Android (APK / PlayStore)", checked: false },
      { id: "cst-3", label: "Integrasi Payment Gateway (Tripay, Midtrans, Xendit)", checked: false },
      { id: "cst-4", label: "Integrasi WhatsApp Notifikasi Otomatis", checked: true },
      { id: "cst-5", label: "Sistem Manajemen Inventori / Gudang", checked: false },
      { id: "cst-6", label: "Multi Role & Keamanan Enkripsi Data", checked: true },
    ],
  };

  function renderFeatures(type) {
    const list = featuresMap[type] || [];
    featureListContainer.innerHTML = "";

    list.forEach((item) => {
      const wrapper = document.createElement("label");
      wrapper.className = "flex items-center space-x-3 p-3 rounded-xl bg-slate-900/60 border border-slate-800 hover:border-indigo-500/50 cursor-pointer transition text-sm text-slate-300";
      wrapper.innerHTML = `
        <input type="checkbox" value="${item.label}" ${item.checked ? "checked" : ""} class="est-feature-checkbox w-4 h-4 text-indigo-600 bg-slate-950 border-slate-700 rounded focus:ring-indigo-500 focus:ring-offset-slate-900">
        <span>${item.label}</span>
      `;
      featureListContainer.appendChild(wrapper);
    });
  }

  // Render initial features
  renderFeatures(appTypeSelect.value);

  // Re-render when category changed
  appTypeSelect.addEventListener("change", function () {
    renderFeatures(this.value);
  });

  // Handle Form Submit
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const name = document.getElementById("lead-name").value.trim() || "Calon Klien";
      const org = document.getElementById("lead-org").value.trim() || "Pribadi/Usaha";
      const appTypeSelectedText = appTypeSelect.options[appTypeSelect.selectedIndex].text;
      const notes = document.getElementById("lead-notes").value.trim();

      const selectedFeatures = [];
      const checkboxes = document.querySelectorAll(".est-feature-checkbox:checked");
      checkboxes.forEach((cb) => {
        selectedFeatures.push("• " + cb.value);
      });

      const featureText = selectedFeatures.length > 0 ? selectedFeatures.join("\n") : "- Konsultasi langsung fitur rekomendasi";

      // Susun pesan WhatsApp yang rapi dan profesional
      let message = `Halo Vega MediaPro, saya ingin konsultasi pembuatan aplikasi:\n\n`;
      message += `👤 *Nama:* ${name}\n`;
      message += `🏢 *Usaha / Sekolah:* ${org}\n`;
      message += `💻 *Kategori Aplikasi:* ${appTypeSelectedText}\n\n`;
      message += `📋 *Kebutuhan Fitur:*\n${featureText}\n\n`;
      if (notes) {
        message += `📝 *Catatan Tambahan:* ${notes}\n\n`;
      }
      message += `Mohon informasi estimasi biaya, estimasi waktu pengerjaan, dan jadwal diskusinya. Terima kasih!`;

      const encodedMessage = encodeURIComponent(message);
      const waUrl = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodedMessage}`;

      window.open(waUrl, "_blank");
    });
  }
}

/* =========================================================================
   5. QUICK WHATSAPP CTA CLICK HANDLERS
   ========================================================================= */
window.openWhatsAppDirect = function (customTopic = "") {
  let message = `Halo Vega MediaPro, saya tertarik untuk konsultasi jasa pembuatan aplikasi.`;
  if (customTopic) {
    message = `Halo Vega MediaPro, saya tertarik untuk konsultasi pembuatan: *${customTopic}*. Mohon info detail paket dan demonya.`;
  }
  const waUrl = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
  window.open(waUrl, "_blank");
};

/* =========================================================================
   6. SMOOTH SCROLLING
   ========================================================================= */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const targetId = this.getAttribute("href");
      if (targetId === "#") return;
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        e.preventDefault();
        targetElement.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });
}
