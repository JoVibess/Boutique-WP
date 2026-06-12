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

  function setStage(modal, state) {
    const dialog = modal.querySelector(".dressme-modal__dialog");
    if (!dialog) {
      return;
    }
    dialog.setAttribute("data-dressme-state", state);
    modal.querySelectorAll("[data-dressme-stage]").forEach(function (stage) {
      stage.hidden = stage.getAttribute("data-dressme-stage") !== state;
    });
  }

  function formatTimer(elapsedMs) {
    const totalSeconds = Math.floor(elapsedMs / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, "0")}`;
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

  function buildTryOnStatusBody(jobId) {
    const body = new URLSearchParams();

    body.set("action", "dressme_try_on_status");
    body.set("nonce", config.statusNonce || "");
    body.set("job_id", jobId);

    return body;
  }

  function buildDownloadUrl(imageUrl, jobId) {
    const params = new URLSearchParams();
    params.set("action", "dressme_download_image");
    params.set("nonce", config.downloadNonce || "");
    params.set("image_url", imageUrl);
    if (jobId) {
      params.set("job_id", jobId);
    }
    return `${config.ajaxUrl}?${params.toString()}`;
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

    if (missingFields.length === 1 && missingFields[0] === "api_secret") {
      return "DressMe is missing the API secret in WooCommerce settings.";
    }

    return "DressMe is missing one or more API credentials in WooCommerce settings.";
  }

  function resetPreview(preview, removePhotoButton) {
    preview.innerHTML = "<span>Your photo will appear here.</span>";

    if (removePhotoButton) {
      preview.appendChild(removePhotoButton);
      removePhotoButton.hidden = true;
    }
  }

  function clearGeneratedResult(modal) {
    const generatedMedia = modal.querySelector("[data-dressme-generated-media]");
    const generatedCaption = modal.querySelector("[data-dressme-generated-caption]");
    const downloadLink = modal.querySelector("[data-dressme-download]");

    if (generatedMedia) {
      generatedMedia.innerHTML = "";
    }

    if (generatedCaption) {
      generatedCaption.textContent =
        (config.messages && config.messages.previewDefault) || "Your generated preview will appear here.";
    }

    if (downloadLink) {
      downloadLink.setAttribute("href", "#");
      downloadLink.removeAttribute("download");
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
    const cameraInput = modal.querySelector("[data-dressme-camera-input]");
    const cameraLabel = modal.querySelector("[data-dressme-open-camera]");
    const cameraVideo = modal.querySelector("[data-dressme-camera-video]");
    const cameraSnapshot = modal.querySelector("[data-dressme-camera-snapshot]");
    const cameraShootBtn = modal.querySelector("[data-dressme-camera-shoot]");
    const cameraRetakeBtn = modal.querySelector("[data-dressme-camera-retake]");
    const cameraUseBtn = modal.querySelector("[data-dressme-camera-use]");
    const cameraCancelBtn = modal.querySelector("[data-dressme-camera-cancel]");
    const generateButton = modal.querySelector("[data-dressme-generate]");
    const cameraStatus = modal.querySelector("[data-dressme-camera-status]");
    const resultCaption = modal.querySelector("[data-dressme-result-caption]");
    const removePhotoButton = modal.querySelector("[data-dressme-remove-photo]");
    const timerElement = modal.querySelector("[data-dressme-timer]");
    let statusPollTimer = null;
    let activeJobId = "";
    let pollRequestInFlight = false;
    let timerIntervalId = null;
    let timerStartedAt = 0;
    let webcamStream = null;
    let capturedDataUrl = "";

    function isMobileDevice() {
      if (navigator.userAgentData && typeof navigator.userAgentData.mobile === "boolean") {
        return navigator.userAgentData.mobile;
      }
      return window.matchMedia("(pointer: coarse)").matches;
    }

    const canUseDesktopWebcam =
      !isMobileDevice() &&
      typeof navigator.mediaDevices !== "undefined" &&
      typeof navigator.mediaDevices.getUserMedia === "function";

    setStage(modal, "idle");
    hydrateProductPreview(modal);

    function startTimer() {
      stopTimer();
      timerStartedAt = Date.now();
      if (timerElement) {
        timerElement.textContent = "0:00";
      }
      timerIntervalId = window.setInterval(function () {
        if (timerElement) {
          timerElement.textContent = formatTimer(Date.now() - timerStartedAt);
        }
      }, 1000);
    }

    function stopTimer() {
      if (timerIntervalId) {
        window.clearInterval(timerIntervalId);
        timerIntervalId = null;
      }
    }

    function stopStatusPolling() {
      if (statusPollTimer) {
        window.clearTimeout(statusPollTimer);
        statusPollTimer = null;
      }

      activeJobId = "";
      pollRequestInFlight = false;
    }

    function scheduleNextStatusPoll(jobId, delay = 2500) {
      if (statusPollTimer) {
        window.clearTimeout(statusPollTimer);
      }
      activeJobId = jobId;
      statusPollTimer = window.setTimeout(function () {
        refreshTryOnStatus(jobId).catch(function (error) {
          stopStatusPolling();
          stopTimer();
          setStage(modal, "idle");
          updateFeedback(modal, error.message || config.messages.statusFailed);

          if (selectedCustomerImage) {
            generateButton.removeAttribute("disabled");
          }
        });
      }, delay);
    }

    function resetModalState() {
      stopStatusPolling();
      stopTimer();
      stopWebcam();
      resetCameraStageUi();
      selectedCustomerImage = "";

      if (fileInput) {
        fileInput.value = "";
      }

      if (cameraInput) {
        cameraInput.value = "";
      }

      resetPreview(preview, removePhotoButton);
      clearGeneratedResult(modal);
      hydrateProductPreview(modal);
      setStage(modal, "idle");

      if (resultCaption) {
        resultCaption.textContent = config.messages.previewDefault || "Your generated preview will appear here.";
      }

      if (cameraStatus) {
        cameraStatus.textContent = "Choose a photo from your camera or your device.";
      }

      if (config.isConfigured) {
        generateButton.setAttribute("disabled", "disabled");
        updateFeedback(modal, config.messages.missingPhoto);
      } else {
        generateButton.setAttribute("disabled", "disabled");
        updateFeedback(modal, getNotConfiguredMessage());
      }
    }

    async function compressImageFile(file, maxDimension = 1024, quality = 0.82) {
      const dataUrl = await new Promise(function (resolve, reject) {
        const reader = new FileReader();

        reader.onload = function () {
          resolve(String(reader.result || ""));
        };

        reader.onerror = reject;
        reader.readAsDataURL(file);
      });

      const image = await new Promise(function (resolve, reject) {
        const img = new Image();

        img.onload = function () {
          resolve(img);
        };

        img.onerror = reject;
        img.src = dataUrl;
      });

      const scale = Math.min(1, maxDimension / Math.max(image.width, image.height));
      const targetWidth = Math.max(1, Math.round(image.width * scale));
      const targetHeight = Math.max(1, Math.round(image.height * scale));
      const canvas = document.createElement("canvas");
      const context = canvas.getContext("2d");

      if (!context) {
        return dataUrl;
      }

      canvas.width = targetWidth;
      canvas.height = targetHeight;
      context.drawImage(image, 0, 0, targetWidth, targetHeight);

      return canvas.toDataURL("image/jpeg", quality);
    }

    async function refreshTryOnStatus(jobId) {
      if (pollRequestInFlight) {
        return;
      }

      let response;
      let payload;
      let data;

      pollRequestInFlight = true;

      try {
        response = await fetch(config.ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: buildTryOnStatusBody(jobId),
        });
        payload = await response.json();
        data = payload && payload.data ? payload.data : {};
      } finally {
        pollRequestInFlight = false;
      }

      if (activeJobId !== jobId) {
        return;
      }

      if (!response.ok || !payload.success) {
        throw new Error(data.message || config.messages.statusFailed);
      }

      if (data.status === "completed" && data.generated_image_url) {
        stopStatusPolling();
        stopTimer();
        setResultImage(modal, data.generated_image_url, jobId, config.messages.completed);
        setStage(modal, "result");
        updateFeedback(modal, config.messages.completed);

        if (selectedCustomerImage) {
          generateButton.removeAttribute("disabled");
        }

        return;
      }

      if (data.status === "failed" || data.status === "rejected") {
        stopStatusPolling();
        stopTimer();
        setStage(modal, "idle");
        updateFeedback(modal, data.error_message || config.messages.failed);

        if (selectedCustomerImage) {
          generateButton.removeAttribute("disabled");
        }

        return;
      }

      if (data.status === "processing" || data.status === "received") {
        scheduleNextStatusPoll(jobId);
      }
    }

    openButton.addEventListener("click", function () {
      resetModalState();
      openModal(modal);
    });

    modal.querySelectorAll("[data-dressme-close-modal]").forEach(function (element) {
      element.addEventListener("click", function () {
        resetModalState();
        closeModal(modal);
      });
    });

    function stopWebcam() {
      if (webcamStream) {
        webcamStream.getTracks().forEach(function (track) {
          track.stop();
        });
        webcamStream = null;
      }
      if (cameraVideo) {
        cameraVideo.srcObject = null;
      }
    }

    function resetCameraStageUi() {
      capturedDataUrl = "";
      if (cameraSnapshot) {
        cameraSnapshot.hidden = true;
        cameraSnapshot.src = "";
      }
      if (cameraVideo) {
        cameraVideo.hidden = false;
      }
      if (cameraShootBtn) cameraShootBtn.hidden = false;
      if (cameraCancelBtn) cameraCancelBtn.hidden = false;
      if (cameraRetakeBtn) cameraRetakeBtn.hidden = true;
      if (cameraUseBtn) cameraUseBtn.hidden = true;
    }

    async function openDesktopWebcam() {
      resetCameraStageUi();
      setStage(modal, "camera");
      try {
        webcamStream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 960 } },
          audio: false,
        });
        if (cameraVideo) {
          cameraVideo.srcObject = webcamStream;
          await cameraVideo.play().catch(function () {});
        }
      } catch (error) {
        stopWebcam();
        setStage(modal, "idle");
        if (cameraInput) {
          cameraInput.click();
        }
      }
    }

    function takeSnapshot() {
      if (!cameraVideo || !cameraVideo.videoWidth) {
        return;
      }
      const canvas = document.createElement("canvas");
      const width = cameraVideo.videoWidth;
      const height = cameraVideo.videoHeight;
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext("2d");
      if (!ctx) {
        return;
      }
      ctx.drawImage(cameraVideo, 0, 0, width, height);
      capturedDataUrl = canvas.toDataURL("image/jpeg", 0.92);

      if (cameraSnapshot) {
        cameraSnapshot.src = capturedDataUrl;
        cameraSnapshot.hidden = false;
      }
      if (cameraVideo) {
        cameraVideo.hidden = true;
      }
      if (cameraShootBtn) cameraShootBtn.hidden = true;
      if (cameraCancelBtn) cameraCancelBtn.hidden = true;
      if (cameraRetakeBtn) cameraRetakeBtn.hidden = false;
      if (cameraUseBtn) cameraUseBtn.hidden = false;
    }

    function retakeSnapshot() {
      capturedDataUrl = "";
      if (cameraSnapshot) {
        cameraSnapshot.hidden = true;
        cameraSnapshot.src = "";
      }
      if (cameraVideo) {
        cameraVideo.hidden = false;
        cameraVideo.play().catch(function () {});
      }
      if (cameraShootBtn) cameraShootBtn.hidden = false;
      if (cameraCancelBtn) cameraCancelBtn.hidden = false;
      if (cameraRetakeBtn) cameraRetakeBtn.hidden = true;
      if (cameraUseBtn) cameraUseBtn.hidden = true;
    }

    async function useCapturedPhoto() {
      if (!capturedDataUrl) {
        return;
      }
      const blob = await (await fetch(capturedDataUrl)).blob();
      const file = new File([blob], "dressme-camera.jpg", { type: "image/jpeg" });
      stopWebcam();
      setStage(modal, "idle");
      handleFileSelection(file);
    }

    function cancelCamera() {
      stopWebcam();
      resetCameraStageUi();
      setStage(modal, "idle");
    }

    function handleFileSelection(file) {
      if (!file) {
        return;
      }

      updateFeedback(modal, config.messages.compressing || "Optimizing your photo before generation...");

      compressImageFile(file)
        .then(function (compressedImage) {
          selectedCustomerImage = compressedImage;
          clearGeneratedResult(modal);
          stopStatusPolling();
          preview.innerHTML = `<img src="${selectedCustomerImage}" alt="DressMe preview">`;

          if (removePhotoButton) {
            preview.appendChild(removePhotoButton);
            removePhotoButton.hidden = false;
          }

          if (config.isConfigured) {
            generateButton.removeAttribute("disabled");
          }

          feedback.textContent = config.messages.uploadPrompt;
        })
        .catch(function () {
          updateFeedback(modal, config.messages.failed);
        });
    }

    fileInput?.addEventListener("change", function (event) {
      const [file] = event.target.files || [];
      handleFileSelection(file);
    });

    cameraInput?.addEventListener("change", function (event) {
      const [file] = event.target.files || [];
      handleFileSelection(file);
    });

    if (canUseDesktopWebcam && cameraLabel) {
      cameraLabel.addEventListener("click", function (event) {
        event.preventDefault();
        openDesktopWebcam();
      });
    }

    cameraShootBtn?.addEventListener("click", takeSnapshot);
    cameraRetakeBtn?.addEventListener("click", retakeSnapshot);
    cameraUseBtn?.addEventListener("click", useCapturedPhoto);
    cameraCancelBtn?.addEventListener("click", cancelCamera);

    removePhotoButton?.addEventListener("click", function () {
      selectedCustomerImage = "";
      if (fileInput) {
        fileInput.value = "";
      }
      if (cameraInput) {
        cameraInput.value = "";
      }
      resetPreview(preview, removePhotoButton);
      clearGeneratedResult(modal);
      stopStatusPolling();
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
      setStage(modal, "generating");
      startTimer();

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
          resultCaption.textContent = config.messages.processing;
        }

        updateFeedback(modal, formatMessage(config.messages.received, data.job_id || ""));

        if (data.job_id) {
          activeJobId = data.job_id;
          refreshTryOnStatus(data.job_id).catch(function () {
            scheduleNextStatusPoll(data.job_id);
          });
        }
      } catch (error) {
        stopTimer();
        setStage(modal, "idle");
        updateFeedback(modal, error.message || config.messages.failed);
        generateButton.removeAttribute("disabled");
      }
    });
  });

  function setResultImage(modal, imageUrl, jobId, caption) {
    const generatedMedia = modal.querySelector("[data-dressme-generated-media]");
    const generatedCaption = modal.querySelector("[data-dressme-generated-caption]");
    const downloadLink = modal.querySelector("[data-dressme-download]");
    const product = config.productPayload || {};

    if (generatedMedia && imageUrl) {
      generatedMedia.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(
        product.product_title || "DressMe generated try-on"
      )}">`;
    }

    if (generatedCaption && caption) {
      generatedCaption.textContent = caption;
    }

    if (downloadLink && imageUrl) {
      downloadLink.setAttribute("href", buildDownloadUrl(imageUrl, jobId));
      downloadLink.setAttribute(
        "download",
        `${(product.product_title || "dressme-look").replace(/[^a-z0-9_-]+/gi, "-").toLowerCase()}.jpg`
      );
    }
  }
})();
