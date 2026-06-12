(function ($) {
  function parseOverrides($app) {
    const raw = $app.find("[data-dressme-overrides-input]").val() || "[]";

    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function writeOverrides($app, overrides) {
    $app.find("[data-dressme-overrides-input]").val(JSON.stringify(overrides));
  }

  function buildResponsiveWidth(width) {
    const normalizedWidth = String(width || "").trim();

    if (!normalizedWidth || normalizedWidth === "100%" || normalizedWidth.endsWith("%")) {
      return normalizedWidth || "100%";
    }

    return `min(100%, ${normalizedWidth})`;
  }

  function applyPreviewWidth($button, rawWidth) {
    const normalizedWidth = String(rawWidth || "").trim() || "100%";

    $button.css({
      width: "",
      minWidth: "",
      maxWidth: "",
      whiteSpace: "nowrap",
      padding: "0 24px",
      display: "inline-flex",
      alignItems: "center",
      justifyContent: "center",
      fontWeight: 600,
      lineHeight: 1.2,
    });

    if (normalizedWidth === "100%" || normalizedWidth.endsWith("%")) {
      $button.css({
        width: buildResponsiveWidth(normalizedWidth),
      });

      return;
    }

    $button.css({
      width: buildResponsiveWidth(normalizedWidth),
      minWidth: "fit-content",
      maxWidth: "100%",
    });
  }

  function rowTemplate(override) {
    const modeOptions = [
      { value: "force_enable", label: "Activer DressMe" },
      { value: "force_disable", label: "Désactiver DressMe" },
    ]
      .map(
        (option) =>
          `<option value="${option.value}" ${option.value === override.mode ? "selected" : ""}>${option.label}</option>`
      )
      .join("");

    return `
      <tr data-product-id="${override.id}">
        <td>${override.label}</td>
        <td>
          <select class="dressme-override-mode">
            ${modeOptions}
          </select>
        </td>
        <td>
          <button type="button" class="button-link-delete" data-dressme-remove-override>Supprimer</button>
        </td>
      </tr>
    `;
  }

  function renderRows($app, overrides) {
    const $rows = $app.find("[data-dressme-overrides-rows]");

    if (!overrides.length) {
      $rows.html(
        `<tr><td colspan="3">Aucune exception produit configurée pour le moment.</td></tr>`
      );
      return;
    }

    $rows.html(overrides.map(rowTemplate).join(""));
  }

  $(function () {
    const $app = $("[data-dressme-overrides-app]");
    const $previewButton = $("[data-dressme-button-preview]");
    const $validateButton = $("[data-dressme-validate-key]");
    const $validationResult = $("[data-dressme-validation-result]");
    const $apiSecretInput = $("[data-dressme-api-secret]");

    function updateButtonPreview() {
      if (!$previewButton.length) {
        return;
      }

      const label =
        $('input[name="dressme_button_label"]').val() ||
        $previewButton.data("label") ||
        "Essayage virtuel";

      const rawWidth = $('[data-dressme-preview-width]').val() || "100%";
      const height = `${$('[data-dressme-preview-height]').val() || 52}px`;
      const radius = `${$('[data-dressme-preview-radius]').val() || 8}px`;
      const bg = $('[data-dressme-preview-bg]').val() || "#111111";
      const color = $('[data-dressme-preview-color]').val() || "#ffffff";
      const hoverBg = $('[data-dressme-preview-hover-bg]').val() || "#2d2d2d";
      const hoverColor = $('[data-dressme-preview-hover-color]').val() || "#ffffff";

      applyPreviewWidth($previewButton, rawWidth);

      $previewButton
        .text(label)
        .css({
          minHeight: height,
          borderRadius: radius,
          "--dressme-preview-bg": bg,
          "--dressme-preview-color": color,
          "--dressme-preview-hover-bg": hoverBg,
          "--dressme-preview-hover-color": hoverColor,
        });
    }

    if (!$app.length) {
      updateButtonPreview();
    } else {
      let overrides = parseOverrides($app);
      renderRows($app, overrides);

      $app.on("click", "[data-dressme-add-products]", function () {
        const $search = $app.find(".wc-product-search");
        const selectedOptions = $search.find("option:selected");

        selectedOptions.each(function () {
          const productId = parseInt(this.value, 10);
          const label = $(this).text();

          if (!productId || overrides.some((override) => override.id === productId)) {
            return;
          }

          overrides.push({
            id: productId,
            label,
            mode: "force_disable",
          });
        });

        overrides.sort((left, right) => left.label.localeCompare(right.label));
        writeOverrides($app, overrides);
        renderRows($app, overrides);
        $search.val(null).trigger("change");
      });

      $app.on("change", ".dressme-override-mode", function () {
        const $row = $(this).closest("tr");
        const productId = parseInt($row.data("product-id"), 10);
        const override = overrides.find((item) => item.id === productId);

        if (!override) {
          return;
        }

        override.mode = $(this).val();
        writeOverrides($app, overrides);
      });

      $app.on("click", "[data-dressme-remove-override]", function () {
        const $row = $(this).closest("tr");
        const productId = parseInt($row.data("product-id"), 10);

        overrides = overrides.filter((override) => override.id !== productId);
        writeOverrides($app, overrides);
        renderRows($app, overrides);
      });
    }

    $(document).on(
      "input change",
      '.dressme-style-input, input[name="dressme_button_label"]',
      updateButtonPreview
    );

    $(document).on("click", "[data-dressme-toggle-secret]", function () {
      if (!$apiSecretInput.length) {
        return;
      }

      const nextType = $apiSecretInput.attr("type") === "password" ? "text" : "password";
      $apiSecretInput.attr("type", nextType);
    });

    $validateButton.on("click", function () {
      if (!window.dressmeAdmin || !$validationResult.length) {
        return;
      }

      $validateButton.prop("disabled", true);
      $validationResult.removeClass("is-success is-error").text(dressmeAdmin.messages.validating);

      $.post(dressmeAdmin.ajaxUrl, {
        action: "dressme_validate_key",
        nonce: dressmeAdmin.validateNonce,
        api_base_url: $('input[name="dressme_api_base_url"]').val() || "",
        api_key: $('input[name="dressme_api_key"]').val() || "",
        api_secret: $('input[name="dressme_api_secret"]').val() || "",
      })
        .done(function (response) {
          const data = response && response.data ? response.data : {};
          const details = [
            data.store_name,
            Number.isFinite(Number(data.remaining_credits))
              ? `${data.remaining_credits} crédit(s)`
              : "",
          ]
            .filter(Boolean)
            .join(" · ");

          $validationResult
            .addClass("is-success")
            .text(details ? `${dressmeAdmin.messages.success} ${details}` : dressmeAdmin.messages.success);
        })
        .fail(function (xhr) {
          const data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : {};
          const message = data.message || dressmeAdmin.messages.error;

          $validationResult.addClass("is-error").text(message);
        })
        .always(function () {
          $validateButton.prop("disabled", false);
        });
    });

    updateButtonPreview();
  });
})(jQuery);
