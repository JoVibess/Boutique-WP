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

  function buildTryOnRequestBody(visitorId, customerImage) {
    const body = new URLSearchParams();

    body.set("action", "dressme_try_on_request");
    body.set("nonce", config.nonce || "");
    body.set("anonymous_visitor_id", visitorId);
    body.set("customer_image", customerImage);
    body.set("product_payload", JSON.stringify(config.productPayload || {}));

    return body;
  }

  function formatMessage(template, value) {
    return String(template || "").replace("%s", value);
  }

  function escapeHtml(value) {
    return String(value || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function hydrateProductPreview(modal) {
    const product = config.productPayload || {};
    const resultMedia = modal.querySelector("[data-dressme-result-media]");
    const title = modal.querySelector("[data-dressme-product-title]");
    const description = modal.querySelector("[data-dressme-product-description]");

    if (title) {
      title.textContent = product.product_title || "";
    }

    if (description) {
      description.textContent = product.product_description || "";
    }

    if (!resultMedia) {
      return;
    }

    if (product.product_image_url) {
      resultMedia.innerHTML = `<img src="${escapeHtml(product.product_image_url)}" alt="${escapeHtml(
        product.product_title || "DressMe product preview"
      )}">`;
      return;
    }

    resultMedia.innerHTML = "<span>Product preview unavailable.</span>";
  }

  function getNotConfiguredMessage() {
    const missingFields = config.missingConfigurationFields || [];

    if (missingFields.length === 0) {
      return config.messages.notConfigured;
    }

    if (missingFields.length === 1 && missingFields[0] === "api_url") {
      return "DressMe is missing the API URL in WooCommerce settings.";
    }

    if (missingFields.length === 1 && missingFields[0] === "api_key") {
      return "DressMe is missing the API key in WooCommerce settings.";
    }

    return "DressMe is missing both the API URL and the API key in WooCommerce settings.";
  }

  function resetPreview(preview, removePhotoButton) {
    preview.innerHTML = "<span>No photo selected yet.</span>";

    if (removePhotoButton) {
      preview.appendChild(removePhotoButton);
      removePhotoButton.hidden = true;
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const modal = document.querySelector("[data-dressme-modal]");
    const openButton = document.querySelector("[data-dressme-open-modal]");

    if (!modal || !openButton || !config) {
      return;
    }

    hydrateButtonStyles(openButton);

    const visitorId = createVisitorId();
    let selectedCustomerImage = "";

    const preview = modal.querySelector("[data-dressme-preview]");
    const feedback = modal.querySelector("[data-dressme-feedback]");
    const fileInput = modal.querySelector("[data-dressme-file-input]");
    const generateButton = modal.querySelector("[data-dressme-generate]");
    const cameraStatus = modal.querySelector("[data-dressme-camera-status]");
    const resultCaption = modal.querySelector("[data-dressme-result-caption]");
    const removePhotoButton = modal.querySelector("[data-dressme-remove-photo]");

    hydrateProductPreview(modal);

    openButton.addEventListener("click", function () {
      openModal(modal);

      if (!config.isConfigured) {
        updateFeedback(modal, getNotConfiguredMessage());
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
        selectedCustomerImage = String(reader.result || "");
        preview.innerHTML = `<img src="${selectedCustomerImage}" alt="DressMe preview">`;

        if (removePhotoButton) {
          preview.appendChild(removePhotoButton);
          removePhotoButton.hidden = false;
        }

        if (config.isConfigured) {
          generateButton.removeAttribute("disabled");
        }

        feedback.textContent = config.messages.uploadPrompt;
      };

      reader.readAsDataURL(file);
    });

    removePhotoButton?.addEventListener("click", function () {
      selectedCustomerImage = "";
      fileInput.value = "";
      resetPreview(preview, removePhotoButton);
      generateButton.setAttribute("disabled", "disabled");
      feedback.textContent = config.messages.missingPhoto;
    });

    generateButton?.addEventListener("click", async function () {
      if (!config.isConfigured) {
        updateFeedback(modal, getNotConfiguredMessage());
        return;
      }

      if (!selectedCustomerImage) {
        updateFeedback(modal, config.messages.missingPhoto);
        return;
      }

      generateButton.setAttribute("disabled", "disabled");
      updateFeedback(modal, config.messages.sending);

      try {
        const response = await fetch(config.ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: buildTryOnRequestBody(visitorId, selectedCustomerImage),
        });
        const payload = await response.json();
        const data = payload && payload.data ? payload.data : {};

        if (!response.ok || !payload.success) {
          throw new Error(data.message || config.messages.failed);
        }

        if (resultCaption) {
          resultCaption.textContent =
            "Try-on request sent. This product preview will be replaced by the generated look as soon as it is available.";
        }

        updateFeedback(modal, formatMessage(config.messages.received, data.job_id || ""));
      } catch (error) {
        updateFeedback(modal, error.message || config.messages.failed);
        generateButton.removeAttribute("disabled");
      }
    });
  });
})();
