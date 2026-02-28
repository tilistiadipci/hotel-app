<style>
    .media-hero {
        display: flex;
        gap: 18px;
        align-items: stretch;
    }

    .media-list {
        flex: 2;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px;
        padding-top: 0px;
        min-height: 65vh;
        max-height: 80vh;
        overflow-y: auto;
    }

    .media-list-header {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8fafc;
        padding: 10px 0px;
    }

    .media-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        transition: background 0.15s ease;
        cursor: pointer;
        background: #fff;
        border: 1px solid #e5e7eb;
    }

    .media-item:hover {
        background: #eef2ff;
    }

    .media-thumb {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 8px;
        background: #f1f5f9;
    }

    .media-meta {
        color: #6b7280;
        font-size: 12px;
    }

    .media-upload {
        flex: 1;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        background: #fff;
        min-height: 65vh;
        max-height: 80vh;
        overflow-y: auto;
    }

    .dropzone {
        border: 1px dashed #94a3b8;
        border-radius: 12px;
        padding: 22px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
    }

    .dropzone:hover {
        border-color: #3b82f6;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: #e5e7eb;
        font-size: 12px;
        color: #374151;
    }

    .usage-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        margin-top: 12px;
    }

    .pending-file {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 6px;
        background: #fff;
    }

    .pending-files-wrap {
        max-height: 300px;
        overflow-y: auto;
    }

    /* Custom modal (reuse pattern from other pages) */
    .custom-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 5000;
    }

    .custom-modal.is-open {
        display: flex;
    }

    .custom-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
    }

    .custom-modal__dialog {
        position: relative;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        max-width: 720px;
        width: 92%;
        z-index: 1;
        padding: 16px;
    }

    .custom-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .custom-modal__title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .custom-modal__close {
        border: none;
        background: transparent;
        font-size: 24px;
        line-height: 1;
        padding: 0 4px;
        cursor: pointer;
    }

    .custom-modal__body {
        max-height: 60vh;
        overflow-y: auto;
    }

    body.custom-modal-open {
        overflow: hidden;
    }

    .swal-text {
        text-align: center;
    }
</style>
