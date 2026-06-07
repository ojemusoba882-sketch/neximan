<div id="{{ uc_id }}_container" class="brava-3d-builder">
    <div class="brava-builder-container">
        
        <!-- Model Selector Tabs -->
        <div class="brava-model-tabs">
            <!-- Model 1 (Always Active) -->
            <button class="brava-model-tab active" data-model="1">{{ model_1_title|default('مدل ۱') }}</button>
            
            <!-- Model 2 -->
            {% if enable_model_2 == "true" or enable_model_2 == 1 %}
            <button class="brava-model-tab" data-model="2">{{ model_2_title|default('مدل ۲') }}</button>
            {% endif %}
            <!-- Model 3 -->
            {% if enable_model_3 == "true" or enable_model_3 == 1 %}
            <button class="brava-model-tab" data-model="3">{{ model_3_title|default('مدل ۳') }}</button>
            {% endif %}
        </div>
        <div class="brava-builder-header">
            <h2>{{ title_text }}</h2>
            <p>{{ subtitle_text }}</p>
        </div>
        <div class="brava-builder-grid">
            <!-- Preview Area -->
            <div class="brava-preview-area">
                <div class="brava-3d-stage">
                    <div class="brava-sofa-display">
                         <img id="main_sofa_image" src="" alt="Sofa Preview" />
                    </div>
                </div>
                <div class="brava-config-info">
                    <div class="brava-config-info-item">
                        <strong class="sofaLabel">انتخاب کنید</strong>
                        <span>مدل</span>
                    </div>
                </div>
            </div>
            <!-- Control Panel -->
            <div class="brava-control-panel">
                <div class="brava-control-section">
                    <label class="brava-control-label">چیدمان مبل</label>
                    <div class="brava-layout-options">
                        <div class="brava-layout-btn" data-layout="2">{{ label_2_seat }}</div>
                        <div class="brava-layout-btn active" data-layout="3">{{ label_3_seat }}</div>
                        <div class="brava-layout-btn" data-layout="4">{{ label_4_seat }}</div>
                        <div class="brava-layout-btn" data-layout="5">{{ label_5_seat }}</div>
                        <div class="brava-layout-btn" data-layout="6">{{ label_6_seat }}</div>
                        <div class="brava-layout-btn" data-layout="7">{{ label_7_seat }}</div>
                        <div class="brava-layout-btn" data-layout="L">{{ label_l_shape }}</div>
                    </div>
                </div>
                <div class="brava-control-section">
                    <label class="brava-control-label">رنگ پارچه</label>
                    <div class="brava-color-options">
                        {{ put_items() }}
                    </div>
                </div>
                <button class="brava-builder-cta">
                    <i class="fas fa-shopping-cart"></i>
                    {{ button_text }}
                </button>
            </div>
        </div>
    </div>
    
    <!-- Data Script: Holds images for ALL models -->
    <script type="application/json" id="sofa_data_all">
    {
        "1": {
            "2": "{{ img_2_seat }}", "3": "{{ img_3_seat }}", "4": "{{ img_4_seat }}", 
            "5": "{{ img_5_seat }}", "6": "{{ img_6_seat }}", "7": "{{ img_7_seat }}", "L": "{{ img_l_shape }}"
        },
        "2": {
            "2": "{{ img_2_seat_m2 }}", "3": "{{ img_3_seat_m2 }}", "4": "{{ img_4_seat_m2 }}", 
            "5": "{{ img_5_seat_m2 }}", "6": "{{ img_6_seat_m2 }}", "7": "{{ img_7_seat_m2 }}", "L": "{{ img_l_shape_m2 }}"
        },
        "3": {
            "2": "{{ img_2_seat_m3 }}", "3": "{{ img_3_seat_m3 }}", "4": "{{ img_4_seat_m3 }}", 
            "5": "{{ img_5_seat_m3 }}", "6": "{{ img_6_seat_m3 }}", "7": "{{ img_7_seat_m3 }}", "L": "{{ img_l_shape_m3 }}"
        }
    }
    </script>
</div>