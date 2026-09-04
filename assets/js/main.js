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

/* =========================================================================
   7. DUITKU SANDBOX CHECKOUT MODAL & SIMULATION
   ========================================================================= */
let currentCheckoutItem = { name: "Paket POS Standar", price: 2500000 };

window.openCheckoutModal = function(packageName, price) {
  currentCheckoutItem = { name: packageName, price: price };
  const modal = document.getElementById("checkout-modal");
  const modalTitle = document.getElementById("checkout-item-title");
  const modalPrice = document.getElementById("checkout-item-price");
  const invoiceCode = document.getElementById("checkout-invoice-code");
  
  if (modalTitle) modalTitle.textContent = packageName;
  if (modalPrice) modalPrice.textContent = "Rp " + price.toLocaleString("id-ID");
  if (invoiceCode) invoiceCode.textContent = "VMP-" + Math.floor(100000 + Math.random() * 900000);
  
  // Reset step views
  const stepForm = document.getElementById("checkout-step-form");
  const stepPay = document.getElementById("checkout-step-payment");
  const stepSuccess = document.getElementById("checkout-step-success");
  if (stepForm) stepForm.classList.remove("hidden");
  if (stepPay) stepPay.classList.add("hidden");
  if (stepSuccess) stepSuccess.classList.add("hidden");

  if (modal) {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  }
};

window.closeCheckoutModal = function() {
  const modal = document.getElementById("checkout-modal");
  if (modal) {
    modal.classList.add("hidden");
    document.body.style.overflow = "auto";
  }
};

window.processCheckoutToPayment = function(e) {
  if (e) e.preventDefault();
  const method = document.querySelector('input[name="payment-method"]:checked')?.value || "QRIS";

  // Switch to payment view
  document.getElementById("checkout-step-form")?.classList.add("hidden");
  document.getElementById("checkout-step-payment")?.classList.remove("hidden");

  const methodLabel = document.getElementById("payment-method-selected");
  if (methodLabel) methodLabel.textContent = method;

  const payAmount = document.getElementById("payment-amount-display");
  if (payAmount) payAmount.textContent = "Rp " + currentCheckoutItem.price.toLocaleString("id-ID");
};

window.simulatePaymentSuccess = function() {
  document.getElementById("checkout-step-payment")?.classList.add("hidden");
  document.getElementById("checkout-step-success")?.classList.remove("hidden");
};

/* =========================================================================
   8. LEGAL & COMPLIANCE MODALS (TERMS, PRIVACY, REFUND)
   ========================================================================= */
const legalContents = {
  terms: {
    title: "Syarat & Ketentuan Layanan (Terms of Service)",
    body: `
      <p class="mb-3">Selamat datang di <strong>Vega MediaPro</strong>. Dengan memesan layanan jasa pembuatan aplikasi kami, Anda menyetujui ketentuan berikut:</p>
      <ul class="list-disc pl-5 space-y-2 mb-3">
        <li><strong>Ruang Lingkup:</strong> Vega MediaPro menyediakan jasa pembuatan software kustom (POS Kasir, Sistem Informasi Sekolah, dan Web/Mobile App) sesuai kesepakatan spesifikasi awal (Scope of Work).</li>
        <li><strong>Sistem Pembayaran:</strong> Pembayaran dapat dilakukan secara bertahap (DP minimal 30% - 50% di awal) melalui gateway pembayaran resmi Duitku atau transfer bank, dan pelunasan saat sistem selesai diuji coba.</li>
        <li><strong>Hak Cipta & Kepemilikan:</strong> Setelah pelunasan, klien berhak atas akses penuh dan pemanfaatan sistem sesuai perjanjian lisensi.</li>
        <li><strong>Garansi:</strong> Kami memberikan garansi perbaikan bug dan error gratis selama 3 hingga 6 bulan sejak tanggal serah terima.</li>
      </ul>
    `
  },
  privacy: {
    title: "Kebijakan Privasi (Privacy Policy)",
    body: `
      <p class="mb-3">Di <strong>Vega MediaPro</strong>, kami menjaga kerahasiaan data pribadi maupun data bisnis klien:</p>
      <ul class="list-disc pl-5 space-y-2 mb-3">
        <li><strong>Pengumpulan Data:</strong> Kami hanya mengumpulkan informasi yang relevan seperti Nama, Kontak WhatsApp, Email, dan Alamat Usaha untuk keperluan pemesanan serta penagihan invoice.</li>
        <li><strong>Keamanan Data:</strong> Kami tidak pernah menjual atau membagikan data Anda kepada pihak luar tanpa persetujuan Anda.</li>
        <li><strong>Pemrosesan Pembayaran:</strong> Transaksi online diproses aman melalui mitra payment gateway berizin resmi Bank Indonesia (Duitku) menggunakan enkripsi SSL standar perbankan.</li>
      </ul>
    `
  },
  refund: {
    title: "Kebijakan Pengembalian Dana & Pembatalan (Refund Policy)",
    body: `
      <p class="mb-3">Ketentuan pembatalan dan pengembalian dana di <strong>Vega MediaPro</strong>:</p>
      <ul class="list-disc pl-5 space-y-2 mb-3">
        <li><strong>Pembatalan Sebelum Pengerjaan:</strong> Jika pembatalan diajukan sebelum proses rancang bangun dimulai, dana DP dapat dikembalikan dipotong biaya administrasi 10%.</li>
        <li><strong>Pembatalan Saat Pengerjaan Berjalan:</strong> Jika pengerjaan sistem telah berjalan di atas 30%, DP tidak dapat dikembalikan karena telah teralokasi untuk biaya riset dan pengerjaan developer.</li>
        <li><strong>Jaminan Revisi:</strong> Jika ada fitur yang belum sesuai dengan kesepakatan awal (SOW), kami berkomitmen penuh memberikan revisi perbaikan hingga sesuai.</li>
      </ul>
    `
  }
};

window.openPolicyModal = function(type) {
  const content = legalContents[type];
  if (!content) return;
  const modal = document.getElementById("legal-modal");
  const modalTitle = document.getElementById("legal-modal-title");
  const modalBody = document.getElementById("legal-modal-body");
  
  if (modalTitle) modalTitle.textContent = content.title;
  if (modalBody) modalBody.innerHTML = content.body;
  if (modal) {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  }
};

window.closePolicyModal = function() {
  const modal = document.getElementById("legal-modal");
  if (modal) {
    modal.classList.add("hidden");
    document.body.style.overflow = "auto";
  }
};
