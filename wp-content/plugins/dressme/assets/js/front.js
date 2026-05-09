(function () {
  const config = window.dressmeTryOn || null;

  function applyButtonState(button, backgroundColor, textColor) {
    button.style.setProperty("background-color", backgroundColor, "important");
    button.style.setProperty("color", textColor, "important");
  }

  function applyResponsiveWidth(button, style) {
    const rawWidth = style.rawWidth || "100%";

    button.style.removeProperty("width");
    button.style.removeProperty("min-width");
    button.style.removeProperty("max-width");

    if (rawWidth === "100%" || rawWidth.endsWith("%")) {
      button.style.setProperty("width", style.width, "important");
      return;
    }

    button.style.setProperty("width", style.width, "important");
    button.style.setProperty("min-width", "fit-content", "important");
    button.style.setProperty("max-width", "100%", "important");
  }

  function hydrateButtonStyles(button) {
    if (!button || !config || !config.buttonStyle) {
      return;
    }

    const style = config.buttonStyle;

    button.style.setProperty("min-height", style.height, "important");
    button.style.setProperty("padding", "0 24px", "important");
    button.style.setProperty("border-radius", style.radius, "important");
    button.style.setProperty("border", "0", "important");
    button.style.setProperty("box-shadow", "none", "important");
    button.style.setProperty("text-decoration", "none", "important");
    button.style.setProperty("font-weight", "600", "important");
    button.style.setProperty("line-height", "1.2", "important");
    button.style.setProperty("display", "inline-flex", "important");
    button.style.setProperty("align-items", "center", "important");
    button.style.setProperty("justify-content", "center", "important");
    button.style.setProperty("white-space", "nowrap", "important");
    applyResponsiveWidth(button, style);
    applyButtonState(button, style.bgColor, style.textColor);

    button.addEventListener("mouseenter", function () {
      button.classList.add("is-hover");
      applyButtonState(button, style.hoverBgColor, style.hoverTextColor);
    });

    button.addEventListener("mouseleave", function () {
      button.classList.remove("is-hover");
      applyButtonState(button, style.bgColor, style.textColor);
    });

    button.addEventListener("focus", function () {
      button.classList.add("is-hover");
      applyButtonState(button, style.hoverBgColor, style.hoverTextColor);
    });

    button.addEventListener("blur", function () {
      button.classList.remove("is-hover");
      applyButtonState(button, style.bgColor, style.textColor);
    });
  }

  function createVisitorId() {
    const storageKey = "dressme_visitor_id";
    let visitorId = window.localStorage.getItem(storageKey);

    if (!visitorId) {
      visitorId = `dm_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
      window.localStorage.setItem(storageKey, visitorId);
    }

    return visitorId;
  }

  function openModal(modal) {
    modal.hidden = false;
    document.body.classList.add("dressme-modal-open");
  }

  function closeModal(modal) {
    modal.hidden = true;
    document.body.classList.remove("dressme-modal-open");
  }

  function updateFeedback(modal, message) {
    const feedback = modal.querySelector("[data-dressme-feedback]");

    if (feedback) {
      feedback.textContent = message;
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const modal = document.querySelector("[data-dressme-modal]");
    const openButton = document.querySelector("[data-dressme-open-modal]");

    if (!modal || !openButton || !config) {
      return;
    }

    hydrateButtonStyles(openButton);

    createVisitorId();

    const preview = modal.querySelector("[data-dressme-preview]");
    const feedback = modal.querySelector("[data-dressme-feedback]");
    const fileInput = modal.querySelector("[data-dressme-file-input]");
    const generateButton = modal.querySelector("[data-dressme-generate]");
    const cameraStatus = modal.querySelector("[data-dressme-camera-status]");

    openButton.addEventListener("click", function () {
      openModal(modal);

      if (!config.isConfigured) {
        updateFeedback(modal, config.messages.notConfigured);
      } else {
        updateFeedback(
          modal,
          `Configuration prête. Quota anonyme actuel: ${config.anonymousDailyQuota} génération(s) par jour.`
        );
      }
    });

    modal.querySelectorAll("[data-dressme-close-modal]").forEach(function (element) {
      element.addEventListener("click", function () {
        closeModal(modal);
      });
    });

    modal.querySelector("[data-dressme-open-camera]")?.addEventListener("click", async function () {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        cameraStatus.textContent = config.messages.cameraUnavailable;
        return;
      }

      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        cameraStatus.textContent = "Camera ready. Real capture flow will be connected in the next phase.";
        stream.getTracks().forEach((track) => track.stop());
      } catch (error) {
        cameraStatus.textContent = "Camera access was denied. You can still upload a photo.";
      }
    });

    fileInput?.addEventListener("change", function (event) {
      const [file] = event.target.files || [];

      if (!file) {
        return;
      }

      const reader = new FileReader();
      reader.onload = function () {
        preview.innerHTML = `<img src="${reader.result}" alt="DressMe preview">`;
        generateButton.removeAttribute("disabled");
        feedback.textContent = config.messages.uploadPrompt;
      };

      reader.readAsDataURL(file);
    });

    generateButton?.addEventListener("click", function () {
      feedback.textContent =
        "Le plugin est prêt pour l’intégration Symfony. La génération réelle sera branchée dans la prochaine phase.";
    });
  });
})();
