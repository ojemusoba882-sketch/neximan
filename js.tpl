const containerId = "{{ uc_id }}_container";
const container = document.getElementById(containerId);
if(container) {
    const mainImage = container.querySelector('#main_sofa_image');
    const displayContainer = container.querySelector('.brava-sofa-display');
    const layoutBtns = container.querySelectorAll('.brava-layout-btn');
    const modelTabs = container.querySelectorAll('.brava-model-tab');
    const infoLabel = container.querySelector('.sofaLabel');
    // 1. Load Data for ALL models
    const dataScript = container.querySelector('#sofa_data_all');
    let allModelsData = {};
    try {
        allModelsData = JSON.parse(dataScript.textContent);
    } catch(e) {
        console.error('Error parsing sofa data', e);
    }
    // State Variables
    let currentModelId = "1"; // Default model
    let currentLayoutId = "3"; // Default layout
    
    // --- init Functions ---
    function init() {
        // Init Layout Buttons
        const defaultBtn = container.querySelector(`.brava-layout-btn[data-layout="${currentLayoutId}"]`);
        if(defaultBtn) defaultBtn.classList.add('active');
        // Init Colors
        setTimeout(initColors, 100);
        // Render Initial View
        renderView();
    }
    function initColors() {
        const colorSwatches = container.querySelectorAll('.brava-color-swatch');
        if(colorSwatches.length > 0) {
            const firstSwatch = colorSwatches[0];
            const defaultColor = firstSwatch.getAttribute('data-color');
            
            if(!container.querySelector('.brava-color-swatch.active')) {
                firstSwatch.classList.add('active');
                displayContainer.style.setProperty('--sofa-color', defaultColor);
            }
            colorSwatches.forEach(swatch => {
                swatch.addEventListener('click', function() {
                    colorSwatches.forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    const color = this.getAttribute('data-color');
                    displayContainer.style.setProperty('--sofa-color', color);
                });
            });
        }
    }
    function renderView() {
        // Find image for current Model AND current Layout
        if(allModelsData[currentModelId]) {
            const imgSrc = allModelsData[currentModelId][currentLayoutId];
            if(imgSrc && mainImage) {
                // Update Image
                mainImage.src = imgSrc;
                displayContainer.style.setProperty('--bg-image', `url("${imgSrc}")`);
            }
        }
        
        // Update Info Text (Optional)
        const activeBtn = container.querySelector('.brava-layout-btn.active');
        if(infoLabel && activeBtn) infoLabel.textContent = activeBtn.innerText;
    }
    // --- Events ---
    // 1. Model Tab Click
    if(modelTabs) {
        modelTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // UI Toggle
                modelTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Logic Toggle
                currentModelId = this.getAttribute('data-model');
                renderView(); // Refresh image with new model
            });
        });
    }
    // 2. Layout Click
    if(layoutBtns){
        layoutBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                layoutBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentLayoutId = this.getAttribute('data-layout');
                renderView(); // Refresh image with new layout
            });
        });
    }
    // Start
    init();
}