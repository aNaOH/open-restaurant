class FileUpload extends HTMLElement {

    uploaded;

    static get observedAttributes() {
        return [
            'accept-image', 
            'accept-video', 
            'min-image-width', 
            'max-image-width', 
            'min-image-height', 
            'max-image-height',
            'min-video-width',
            'max-video-width',
            'min-video-height',
            'max-video-height',
            'max-image-size',
            'max-video-size',
            'image-type',
            'video-type',
            'image-aspect-ratio',
            'video-aspect-ratio'
        ];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
    }

    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
    }

    connectedCallback() {
        this.acceptImage = this.hasAttribute('accept-image') ? this.getAttribute('accept-image') === 'true' : true;
        this.acceptVideo = this.hasAttribute('accept-video') ? this.getAttribute('accept-video') === 'true' : false;
        this.minImageWidth = parseInt(this.getAttribute('min-image-width') || 640);
        this.maxImageWidth = parseInt(this.getAttribute('max-image-width') || 1920);
        this.minImageHeight = parseInt(this.getAttribute('min-image-height') || 360);
        this.maxImageHeight = parseInt(this.getAttribute('max-image-height') || 1080);
        this.minVideoWidth = parseInt(this.getAttribute('min-video-width') || 640);
        this.maxVideoWidth = parseInt(this.getAttribute('max-video-width') || 1920);
        this.minVideoHeight = parseInt(this.getAttribute('min-video-height') || 360);
        this.maxVideoHeight = parseInt(this.getAttribute('max-video-height') || 1080);
        this.maxImageSize = this.getAttribute('max-image-size') || '5MB';
        this.maxVideoSize = this.getAttribute('max-video-size') || '5MB';
        this.imageType = this.getAttribute('image-type') || 'png,jpeg,webp';
        this.videoType = this.getAttribute('video-type') || 'mp4,webm';
        this.imageAspectRatio = this.getAttribute('image-aspect-ratio') || 'any';
        this.videoAspectRatio = this.getAttribute('video-aspect-ratio') || 'any';
    
        this.handleFileChange = this.handleFileChange.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);

        this.render();
        this.addEventListeners();
    }
  
    render() {
      this.shadowRoot.innerHTML = `
        <style>
          .drop-area {
            width: 100%;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            cursor: pointer;
          }
          .drop-area.drag-over {
            background-color: rgba(0, 0, 0, 0.1);
          }
          .file-info {
            margin-top: 10px;
          }
          input[type="file"] {
            display: none;
          }
          .preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 200px;
          }
        </style>
        <div class="drop-area" id="drop-area">
          <p>Arrastra o haz clic para seleccionar un archivo</p>
          <input type="file" id="file-input" accept="${this.getAcceptedTypes()}">
        </div>
        <div class="file-info" id="file-info"></div>
        <div id="preview-container"></div>
      `;
    }
  
    addEventListeners() {
      const dropArea = this.shadowRoot.querySelector('#drop-area');
      const fileInput = this.shadowRoot.querySelector('#file-input');
      dropArea.addEventListener('click', () => fileInput.click());
      dropArea.addEventListener('dragover', this.handleDragOver);
      dropArea.addEventListener('drop', this.handleDrop);
      fileInput.addEventListener('change', this.handleFileChange);
    }
  
    handleDragOver(event) {
      event.preventDefault();
      this.shadowRoot.querySelector('#drop-area').classList.add('drag-over');
    }
  
    handleDrop(event) {
      event.preventDefault();
      this.shadowRoot.querySelector('#drop-area').classList.remove('drag-over');
      const files = event.dataTransfer.files;
      this.processFiles(files);
    }
  
    handleFileChange(event) {
      const files = event.target.files;
      this.processFiles(files);
    }
  
    processFiles(files) {
      if (files.length === 0) return;
      
      const file = files[0];
      const fileType = file.type.split('/')[0];

      this.uploaded = files;
  
      this.shadowRoot.querySelector('#file-info').innerHTML = '';
      // Limpiar cualquier previsualización anterior
      this.shadowRoot.querySelector('#preview-container').innerHTML = '';
  
      if (fileType === 'image' && this.acceptImage) {
        this.validateImage(file);
      } else if (fileType === 'video' && this.acceptVideo) {
        this.validateVideo(file);
      } else {
        this.displayError(`Tipo de archivo no soportado: ${file.type}`);
      }
    }
  
    validateImage(file) {
        const img = new Image();
        img.onload = () => {
          const aspectRatio = img.width / img.height;
          if (img.width < this.minImageWidth || img.width > this.maxImageWidth || img.height < this.minImageHeight || img.height > this.maxImageHeight) {
            this.displayError(`Las dimensiones de la imagen deben estar entre ${this.minImageWidth}x${this.minImageHeight} y ${this.maxImageWidth}x${this.maxImageHeight}`);
          } else if (file.size > this.parseSize(this.maxImageSize)) {
            this.displayError(`El archivo excede el tamaño máximo de ${this.maxImageSize}`);
          } else if (!this.isAcceptedImageType(file)) {
            this.displayError(`Tipo de imagen no permitido: ${file.type}`);
          } else if (this.imageAspectRatio !== 'any' && !this.isAcceptedAspectRatio(aspectRatio, this.imageAspectRatio)) {
            this.displayError(`La relación de aspecto de la imagen debe ser ${this.imageAspectRatio}`);
          } else {
            this.previewImage(file);
          }
        };
        img.src = URL.createObjectURL(file);
    }

    isAcceptedImageType(file) {
        const fileExtension = file.type.split('/')[1];
        const acceptedTypes = this.imageType.split(',').map(type => type.trim());
        return acceptedTypes.includes(fileExtension);
    }
  
    validateVideo(file) {
        const video = document.createElement('video');
        video.onloadedmetadata = () => {
          const aspectRatio = video.videoWidth / video.videoHeight;
          if (video.videoWidth < this.minVideoWidth || video.videoWidth > this.maxVideoWidth || video.videoHeight < this.minVideoHeight || video.videoHeight > this.maxVideoHeight) {
            this.displayError(`Las dimensiones del video deben estar entre ${this.minVideoWidth}x${this.minVideoHeight} y ${this.maxVideoWidth}x${this.maxVideoHeight}`);
          } else if (file.size > this.parseSize(this.maxVideoSize)) {
            this.displayError(`El archivo excede el tamaño máximo de ${this.maxVideoSize}`);
          } else if (!this.isAcceptedVideoType(file)) {
            this.displayError(`Tipo de video no permitido: ${file.type}`);
          } else if (this.videoAspectRatio !== 'any' && !this.isAcceptedAspectRatio(aspectRatio, this.videoAspectRatio)) {
            this.displayError(`La relación de aspecto del video debe ser ${this.videoAspectRatio}`);
          } else {
            this.previewVideo(file);
          }
        };
        video.src = URL.createObjectURL(file);
    }

    isAcceptedVideoType(file) {
        const fileExtension = file.type.split('/')[1];
        const acceptedTypes = this.videoType.split(',').map(type => type.trim());
        return acceptedTypes.includes(fileExtension);
    }
  
    displayError(message) {
      this.shadowRoot.querySelector('#file-info').innerHTML = `<span class="text-red-500">${message}</span>`;
    }
  
    parseSize(size) {
      const match = size.match(/^(\d+)(MB|KB)$/);
      if (!match) return 0;
      const value = parseInt(match[1]);
      const unit = match[2];
      return unit === 'MB' ? value * 1024 * 1024 : value * 1024;
    }
  
    getAcceptedTypes() {
      let types = [];
      if (this.acceptImage){
        for (const imgType of this.imageType.split(',')) {
            types.push('image/' + imgType);
        }
      };
      if (this.acceptVideo){
        for (const videoType of this.videoType.split(',')) {
            types.push('video/' + videoType);
        }
      };
      return types.join(',');
    }

    previewImage(file) {
      const imgElement = document.createElement('img');
      imgElement.src = URL.createObjectURL(file);
      imgElement.classList.add('preview');
      this.shadowRoot.querySelector('#preview-container').appendChild(imgElement);
    }

    previewVideo(file) {
      const videoElement = document.createElement('video');
      videoElement.src = URL.createObjectURL(file);
      videoElement.controls = true;
      videoElement.classList.add('preview');
      this.shadowRoot.querySelector('#preview-container').appendChild(videoElement);
    }

    isAcceptedAspectRatio(aspectRatio, acceptedAspectRatio) {
        const [acceptedWidth, acceptedHeight] = acceptedAspectRatio.split(':').map(Number);
        const acceptedRatio = acceptedWidth / acceptedHeight;
        return Math.abs(aspectRatio - acceptedRatio) < 0.01; // Allow a small margin of error
    }

    getFileInput(){
        return this.uploaded;
    }
}
  
customElements.define('file-upload', FileUpload);