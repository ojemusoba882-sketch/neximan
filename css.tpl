:root {
    --primary: #E8EEEE;
    --secondary: #C0CCD1;
    --accent-1: #F5EFEB;
    --accent-2: #D3EAE7;
    --white: #FFFFFF;
    --dark: #2C3E50;
    --text: #4A5568;
    --green: {{ accent_color }};
}
#{{ uc_id }}_container.brava-3d-builder {
    background: var(--white);
    padding: 60px 2rem;
    font-family: inherit;
    position: relative;
    direction: rtl;
    box-sizing: border-box;
}
#{{ uc_id }}_container * {
    box-sizing: border-box;
    transition: all 0.2s ease;
}
#{{ uc_id }}_container .brava-builder-container {
    max-width: 1400px;
    margin: 0 auto;
}
/* Model Tabs Styles */
#{{ uc_id }}_container .brava-model-tabs {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
#{{ uc_id }}_container .brava-model-tab {
    padding: 10px 25px;
    background: transparent;
    border: 2px solid var(--secondary);
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-light);
    cursor: pointer;
}
#{{ uc_id }}_container .brava-model-tab.active {
    background: var(--dark);
    color: var(--white);
    border-color: var(--dark);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
#{{ uc_id }}_container .brava-model-tab:hover:not(.active) {
    background: var(--primary);
}
#{{ uc_id }}_container .brava-builder-header {
    text-align: center;
    margin-bottom: 3rem;
}
#{{ uc_id }}_container .brava-builder-header h2 {
    font-size: clamp(2rem, 5vw, 4rem);
    font-weight: 900;
    color: var(--dark);
    margin-bottom: 0.5rem;
}
/* Grid Layout */
#{{ uc_id }}_container .brava-builder-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 3rem;
    align-items: start;
}
/* Preview Area */
#{{ uc_id }}_container .brava-preview-area {
    background: linear-gradient(135deg, var(--primary), var(--accent-2));
    border-radius: 30px;
    padding: 2rem;
    min-height: 500px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
#{{ uc_id }}_container .brava-3d-stage {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Sofa Display */
#{{ uc_id }}_container .brava-sofa-display {
    position: relative;
    width: 100%;
    max-width: 800px;
    background-color: var(--sofa-color, transparent);
    
    -webkit-mask-image: var(--bg-image);
    mask-image: var(--bg-image);
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
    
    aspect-ratio: 16/9; 
    transition: background-color 0.3s ease;
}
#{{ uc_id }}_container .brava-sofa-display img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    mix-blend-mode: multiply;
    display: block;
}
@supports not (mask-image: url('')) {
    #{{ uc_id }}_container .brava-sofa-display {
        background-color: transparent !important;
    }
    #{{ uc_id }}_container .brava-sofa-display img {
        mix-blend-mode: normal !important;
    }
}
/* Info Info */
#{{ uc_id }}_container .brava-config-info {
    position: absolute;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    padding: 0.8rem 1.5rem;
    border-radius: 50px;
    display: flex;
    gap: 1.5rem;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    white-space: nowrap;
}
/* Control Panel */
#{{ uc_id }}_container .brava-control-panel {
    background: var(--white);
    border-radius: 25px;
    padding: 2rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    text-align: right;
}
#{{ uc_id }}_container .brava-control-section {
    margin-bottom: 2rem;
}
#{{ uc_id }}_container .brava-control-label {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.8rem;
    display: block;
}
/* Layout Buttons Grid */
#{{ uc_id }}_container .brava-layout-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.5rem;
}
#{{ uc_id }}_container .brava-layout-btn {
    padding: 0.8rem 0.5rem;
    background: var(--primary);
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
}
#{{ uc_id }}_container .brava-layout-btn:hover {
    background: var(--secondary);
}
#{{ uc_id }}_container .brava-layout-btn.active {
    background: var(--green);
    color: white;
}
/* Color Swatches Grid */
#{{ uc_id }}_container .brava-color-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 0.8rem;
}
#{{ uc_id }}_container .brava-color-swatch {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid #e2e8f0; 
    transition: transform 0.2s;
    min-height: 40px; 
}
#{{ uc_id }}_container .brava-color-swatch.active {
    border-color: var(--dark);
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
/* CTA */
#{{ uc_id }}_container .brava-builder-cta {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 1.5rem;
}
@media (max-width: 900px) {
    #{{ uc_id }}_container .brava-builder-grid {
        grid-template-columns: 1fr;
    }
}