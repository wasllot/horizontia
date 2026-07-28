<style>
    /* Vertically Stack Labels and Full Width Inputs */
    .cr-course.cr-create-course .am-themeform .form-group {
        display: block !important;
        width: 100% !important;
    }
    .cr-course.cr-create-course .am-themeform .form-group > label,
    .cr-course.cr-create-course .am-themeform .form-group .cr-titlewrap {
        display: block !important;
        width: 100% !important;
        text-align: left !important;
        margin-bottom: 8px !important;
        max-width: 100% !important;
        flex: none !important;
    }
    .cr-course.cr-create-course .am-themeform .form-group input[type="text"].form-control,
    .cr-course.cr-create-course .am-themeform .form-group input[type="number"].form-control,
    .cr-course.cr-create-course .am-themeform .form-group textarea.form-control,
    .cr-course.cr-create-course .am-themeform .form-group .am-select,
    .cr-course.cr-create-course .am-themeform .form-group .am-custom-editor,
    .cr-course.cr-create-course .am-themeform .form-group .cr-grap-input,
    .cr-course.cr-create-course .am-themeform .form-group .cr-input-wrap,
    .cr-course.cr-create-course .am-themeform .form-group .select2-container {
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
    }
    .cr-course.cr-create-course .am-themeform .form-group-two-wrap {
        display: flex;
        gap: 20px;
        width: 100%;
        flex-wrap: wrap;
    }
    .cr-course.cr-create-course .am-themeform .form-group-two-wrap .form-contro_wrap {
        flex: 1;
        min-width: 0;
        display: block !important;
        width: 100% !important;
    }
    .cr-course.cr-create-course .am-themeform .form-group-two-wrap .form-contro_wrap label {
        display: block !important;
        margin-bottom: 8px !important;
    }
    .cr-course.cr-create-course .am-themeform .form-group-two-wrap .form-contro_wrap .select2-container {
        width: 100% !important;
    }
    
    /* Center align dropzone content */
    .cr-course.cr-create-course .am-uploadfile {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        min-height: 140px;
        padding: 20px;
        position: relative;
    }
    .cr-course.cr-create-course .am-uploadfile em i {
        font-size: 28px;
        color: #888;
        display: block;
        margin-bottom: 8px;
    }
    
    /* Fix drag and drop text overlapping */
    .tk-draganddrop .am-dropfileshadow {
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .tk-draganddrop.am-dragfile .am-dropfileshadow {
        opacity: 1;
        visibility: visible;
    }

    /* Fix uploaded file preview styling */
    .cr-course.cr-create-course .am-uploadedfile {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 15px;
        border: 1px solid #EAEAEA;
        border-radius: 8px;
        margin-top: 15px;
        background: #f9f9f9;
        width: 100%;
    }
    .cr-course.cr-create-course .am-uploadedfile a.venobox img,
    .cr-course.cr-create-course .am-uploadedfile figure img {
        width: 160px !important;
        height: 100px !important;
        object-fit: cover !important;
        border-radius: 6px;
        display: block;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .cr-course.cr-create-course .am-uploadedfile > span {
        flex: 1;
        display: flex;
        flex-direction: column;
        font-size: 14px;
        color: #333;
        line-height: 1.4;
    }
    .cr-course.cr-create-course .am-uploadedfile > span em {
        font-size: 12px;
        color: #888;
        font-style: normal;
    }
    .cr-course.cr-create-course .am-uploadedfile .am-delitem {
        color: #ff4d4f;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    .cr-course.cr-create-course .am-uploadedfile .am-delitem:hover {
        background: #ff4d4f;
        color: #fff;
    }
</style>

<div class="cr-course cr-create-course">
    <div class="container">
        <livewire:courses::course-sidebar :tab="$tab" :id="$id" :tabs="$tabs" />
        <livewire:dynamic-component :component="'courses::course-'.$tab" :tab="$tab" :id="$id" />
    </div>
</div>
