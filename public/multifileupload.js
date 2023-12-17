document.querySelectorAll(".widget-multiupload").forEach((widget) => {
  var activeUploads = 0;
  const filenames = [];
  const pendingUploads = [];
  const maxUploads = 2;

  const form = widget.closest("form");
  const inputFile = widget.querySelector("input[type=file]");
  const inputHidden = widget.querySelector("input[type=hidden]");
  const REQUEST_TOKEN = form.querySelector('input[name="REQUEST_TOKEN"]').value;
  const ulFileList = document.createElement("ul");
  ulFileList.classList.add("filelist", "empty");

  const generateFilename = (filename) => {
    var index = 0;
    const fileparts = filename.split(".");
    const firstfilepart = fileparts[0];
    while (filenames.includes(filename)) {
      index++;
      fileparts[0] = firstfilepart + " (" + index + ")";
      filename = fileparts.join(".");
    }
    filenames.push(filename);
    inputHidden.value = filenames.join(",");
    return filename;
  };

  const createUpload = (file) => {
    var status = "pending";

    const filename = generateFilename(file.name);
    const id = inputFile.id;

    const searchParams = new URLSearchParams({ filename, id });
    const requestUrl = window.location.pathname + "?" + searchParams.toString();

    const formData = new FormData();
    formData.append("file", file);
    formData.append("REQUEST_TOKEN", REQUEST_TOKEN);

    const liFile = document.createElement("li");
    liFile.classList.add("file");

    // Progress circle
    const divProgress = document.createElement("div");
    divProgress.classList.add("progress");
    divProgress.innerHTML = "<i></i>";
    liFile.appendChild(divProgress);

    // Filename
    const divFileName = document.createElement("div");
    divFileName.classList.add("filename");
    divFileName.textContent = filename;
    liFile.appendChild(divFileName);

    // Abort button
    const divAbort = document.createElement("div");
    divAbort.classList.add("delete");
    const abortButton = document.createElement("button");
    abortButton.type = "button";
    abortButton.textContent = "löschen";
    divAbort.appendChild(abortButton);
    liFile.appendChild(divAbort);

    ulFileList.appendChild(liFile);

    const removeFile = () => {
        const index = filenames.indexOf(filename);
        if (index === -1) return;
        filenames.splice(index, 1);
        inputHidden.value = filenames.join(",");
    }

    const updateUl = () => {
      ulFileList.classList.toggle("empty", ulFileList.children.length === 0);
    }

    const xhr = new XMLHttpRequest();

    // Send progress to progress circle
    xhr.upload.addEventListener("progress", (e) => {
      if (e.lengthComputable) {
        divProgress.style.setProperty("--progress", e.loaded / e.total);
      }
    });

    // Remove progress and change color on load
    xhr.addEventListener("load", () => {
      activeUploads--;
      liFile.classList.add(xhr.status === 200 ? "done" : "error");
      if (xhr.status !== 200) removeFile();
      updateUl();
      nextUpload();
    });

    xhr.addEventListener("abort", () => {
      activeUploads--;
      removeFile();
      nextUpload();
    });

    // Start upload
    const start = () => {
      activeUploads++;
      xhr.open("POST", requestUrl, true);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      xhr.send(formData);
    };

    // Delete file
    const deleteFile = () => {
      const xhr = new XMLHttpRequest();
      xhr.open("DELETE", requestUrl, true);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      xhr.send();
      removeFile();
    };

    // Abort upload
    abortButton.addEventListener("click", () => {
      switch (xhr.readyState) {
        case XMLHttpRequest.UNSENT:
          pendingUploads.splice(pendingUploads.indexOf(start), 1);
          break;
        case XMLHttpRequest.OPENED:
          xhr.abort();
          break;
        case XMLHttpRequest.DONE:
          deleteFile();
          break;
      }

      filenames.splice(filenames.indexOf(filename), 1);
      ulFileList.removeChild(liFile);
      updateUl();
    });

    pendingUploads.push(start);
  };

  const nextUpload = () => {
    while (activeUploads < maxUploads && pendingUploads.length > 0) {
      pendingUploads.shift()();
    }
  };

  // Upload files on input change
  inputFile.addEventListener("change", (e) => {
    ulFileList.classList.remove("empty");
    [...e.target.files].forEach(createUpload);
    e.target.value = "";
    nextUpload();
  });

  // Prevent submiting form while uploading
  form.addEventListener("submit", (e) => {
    if (activeUploads > 0) {
      e.preventDefault();
    }
  });

  // Create upload on drop
  widget.addEventListener("drop", (e) => {
    widget.classList.remove("dragover");
    [...e.dataTransfer.files].forEach(createUpload);
    e.preventDefault();
    nextUpload();
  });

  widget.addEventListener("dragover", (e) => {
    widget.classList.add("dragover");
    e.preventDefault();
  });

  widget.addEventListener("dragleave", (e) => {
    widget.classList.remove("dragover");
    e.preventDefault();
  });

  widget.appendChild(ulFileList);
});
