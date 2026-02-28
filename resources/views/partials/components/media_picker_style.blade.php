<style>
    /* Custom modal (independent from Bootstrap) */
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
        max-width: 520px;
        width: 92%;
        max-height: 85vh;
        overflow-y: auto;
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
        padding: 4px 0 8px;
    }

    .custom-modal__footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding-top: 8px;
    }

    body.custom-modal-open {
        overflow: hidden;
    }

    /* Media picker grid */
    .media-picker-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        align-content: start;
        align-items: start;
        max-height: 300px;
        min-height: 300px;
        overflow-y: auto;
        grid-auto-rows: minmax(0, auto);
    }

    .media-picker-item {
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #fafafa;
    }

    .media-picker-item:hover {
        border-color: #4d79f6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .media-picker-thumb {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        background: #f2f2f2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    .media-picker-title {
        font-size: 12px;
        margin-top: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
