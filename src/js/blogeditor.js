/**
 * Portfolio Manager Blog Rich Text Editor
 *
 * Purpose:
 * - Turns each [data-rich-editor] element into a simple rich-text editor.
 * - Keeps the hidden <input name="content"> synced with the editor HTML.
 * - Preserves selected text when toolbar buttons are clicked.
 * - Uses a custom Bold handler because document.execCommand("bold") is unreliable here.
 * - Adds custom blockquote handling so pressing Enter at the end exits the quote.
 */

document.addEventListener("DOMContentLoaded", () => {
    // Find every rich editor on the page.
    const editors = document.querySelectorAll("[data-rich-editor]");

    editors.forEach(editor => initialiseRichEditor(editor));
});

function initialiseRichEditor(editor) {
    // Each editor writes its HTML into a hidden input before form submission.
    const inputId = editor.dataset.input;
    const hiddenInput = document.getElementById(inputId);

    // Each toolbar is connected to its editor by data-toolbar-for.
    const toolbar = document.querySelector(`[data-toolbar-for="${inputId}"]`);

    // Stop safely if the expected editor/toolbar/input markup is missing.
    if (!hiddenInput || !toolbar) {
        return;
    }

    // Store the last valid selection range for this editor.
    const state = {
        savedRange: null
    };

    // Load existing saved content into the editable area.
    editor.innerHTML = hiddenInput.value || "";

    // Save selection after mouse selection.
    editor.addEventListener("mouseup", () => {
        saveEditorSelection(editor, state);
    });

    // Save selection and sync content after typing.
    editor.addEventListener("keyup", () => {
        saveEditorSelection(editor, state);
        syncEditorToInput(editor, hiddenInput);
    });

    // Sync content after paste, deletion, drag/drop, browser formatting, etc.
    editor.addEventListener("input", () => {
        saveEditorSelection(editor, state);
        syncEditorToInput(editor, hiddenInput);
    });

    // Save selection when the editor gains focus.
    editor.addEventListener("focus", () => {
        saveEditorSelection(editor, state);
    });

    // Save selection when clicking inside the editor.
    editor.addEventListener("click", () => {
        saveEditorSelection(editor, state);
    });

    // Handle special keyboard behaviour, such as exiting blockquotes.
    editor.addEventListener("keydown", event => {
        handleEditorKeydown(event, editor, hiddenInput, state);
    });

    // Track selection changes globally, but only save ranges inside this editor.
    document.addEventListener("selectionchange", () => {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);

        if (editor.contains(range.commonAncestorContainer)) {
            state.savedRange = range.cloneRange();
        }
    });

    /**
     * Prevent toolbar buttons from stealing focus from the editor.
     *
     * Without this, clicking Bold/Italic/etc can destroy the selected text range
     * before the command has a chance to run.
     */
    ["mousedown", "pointerdown"].forEach(eventName => {
        toolbar.addEventListener(eventName, event => {
            const button = event.target.closest("button[data-command]");

            if (!button) {
                return;
            }

            event.preventDefault();
        });
    });

    // Handle toolbar button clicks.
    toolbar.addEventListener("click", event => {
        const button = event.target.closest("button[data-command]");

        if (!button) {
            return;
        }

        event.preventDefault();

        const command = button.dataset.command;
        const value = button.dataset.value || null;

        // Restore the user's selected text before doing anything.
        restoreEditorSelection(editor, state);

        /**
         * Custom Bold fix:
         *
         * document.execCommand("bold") is the unreliable command in this editor.
         * Other toolbar commands work, but Bold fails until another style is applied first.
         * So Bold is handled manually using the Selection API.
         */
        if (command === "bold") {
            toggleBold(editor, hiddenInput, state);
            return;
        }

        // Link creation needs validation and a URL prompt.
        if (command === "createLink") {
            handleCreateLink(editor, hiddenInput, state);
            return;
        }

        // Quote needs custom toggle and escape behaviour.
        if (command === "formatBlock" && value === "blockquote") {
            toggleBlockquote(editor, hiddenInput, state);
            return;
        }

        // Headings, paragraphs, and other block formatting.
        if (command === "formatBlock") {
            runEditorCommand(editor, hiddenInput, state, "formatBlock", value);
            return;
        }

        // Standard commands: italic, underline, lists, alignment, clear formatting, etc.
        runEditorCommand(editor, hiddenInput, state, command, value);
    });

    // Final sync before the form submits.
    editor.closest("form")?.addEventListener("submit", () => {
        syncEditorToInput(editor, hiddenInput);
    });
}

function syncEditorToInput(editor, hiddenInput) {
    // The hidden input is the actual form field submitted to PHP.
    hiddenInput.value = editor.innerHTML;
}

function runEditorCommand(editor, hiddenInput, state, command, value = null) {
    // Restore selected text before applying the command.
    restoreEditorSelection(editor, state);

    // Prefer semantic tags over inline CSS when the browser supports it.
    document.execCommand("styleWithCSS", false, false);

    // Run the browser command.
    document.execCommand(command, false, value);

    // Save the updated state.
    saveEditorSelection(editor, state);
    syncEditorToInput(editor, hiddenInput);
}

function saveEditorSelection(editor, state) {
    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);

    // Only save selections that are inside this editor.
    if (!editor.contains(range.commonAncestorContainer)) {
        return;
    }

    state.savedRange = range.cloneRange();
}

function restoreEditorSelection(editor, state) {
    // Focus without scrolling the page around.
    editor.focus({ preventScroll: true });

    const selection = window.getSelection();

    if (!selection) {
        return;
    }

    selection.removeAllRanges();

    // Restore the saved selection if it is still valid.
    if (state.savedRange && editor.contains(state.savedRange.commonAncestorContainer)) {
        selection.addRange(state.savedRange);
        return;
    }

    // Fallback: place cursor at the end of the editor.
    const range = document.createRange();
    range.selectNodeContents(editor);
    range.collapse(false);

    selection.addRange(range);
    state.savedRange = range.cloneRange();
}

function toggleBold(editor, hiddenInput, state) {
    /**
     * Custom Bold behaviour:
     *
     * - If selected text is already inside <strong> or <b>, unwrap it.
     * - Otherwise, wrap the selected content in <strong>.
     * - If no text is selected, fall back to browser bold mode for future typing.
     */
    restoreEditorSelection(editor, state);

    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);

    if (!editor.contains(range.commonAncestorContainer)) {
        return;
    }

    // If there is no highlighted text, use browser bold mode for what the user types next.
    if (selection.isCollapsed) {
        document.execCommand("bold", false, null);
        saveEditorSelection(editor, state);
        syncEditorToInput(editor, hiddenInput);
        return;
    }

    // If the selected text is already inside a bold element, unwrap that element.
    const existingBold = findClosestElementByTags(
        range.commonAncestorContainer,
        ["STRONG", "B"],
        editor
    );

    if (existingBold) {
        unwrapElement(existingBold);
        saveEditorSelection(editor, state);
        syncEditorToInput(editor, hiddenInput);
        return;
    }

    // Wrap the selected content in a semantic <strong> element.
    const strong = document.createElement("strong");

    try {
        const selectedContent = range.extractContents();

        strong.appendChild(selectedContent);
        range.insertNode(strong);

        // Reselect the newly bolded content so the user can keep formatting it.
        const newRange = document.createRange();
        newRange.selectNodeContents(strong);

        selection.removeAllRanges();
        selection.addRange(newRange);

        state.savedRange = newRange.cloneRange();
    } catch (error) {
        /**
         * Fallback:
         *
         * Some complex selections can fail with extractContents/insertNode.
         * If that happens, fall back to browser command rather than doing nothing.
         */
        document.execCommand("bold", false, null);
        saveEditorSelection(editor, state);
    }

    syncEditorToInput(editor, hiddenInput);
}

function unwrapElement(element) {
    /**
     * Replace an element with its children.
     *
     * Example:
     * <strong>Hello</strong>
     * becomes:
     * Hello
     */
    const parent = element.parentNode;

    if (!parent) {
        return;
    }

    while (element.firstChild) {
        parent.insertBefore(element.firstChild, element);
    }

    parent.removeChild(element);
}

function handleCreateLink(editor, hiddenInput, state) {
    restoreEditorSelection(editor, state);

    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0 || selection.toString().trim() === "") {
        alert("Highlight the text you want to turn into a link first.");
        return;
    }

    const url = prompt("Enter the link URL:");

    if (!url) {
        restoreEditorSelection(editor, state);
        return;
    }

    const cleanUrl = url.trim();

    if (!isSafeEditorLink(cleanUrl)) {
        alert("Only http, https, and mailto links are allowed.");
        return;
    }

    document.execCommand("createLink", false, cleanUrl);

    // Add safe attributes to links created by the editor.
    editor.querySelectorAll("a").forEach(link => {
        const href = link.getAttribute("href") || "";

        if (href === cleanUrl) {
            link.setAttribute("target", "_blank");
            link.setAttribute("rel", "noopener noreferrer");
        }
    });

    saveEditorSelection(editor, state);
    syncEditorToInput(editor, hiddenInput);
}

function isSafeEditorLink(url) {
    try {
        const parsed = new URL(url, window.location.origin);

        // Only allow safe, expected protocols.
        return ["http:", "https:", "mailto:"].includes(parsed.protocol);
    } catch {
        return false;
    }
}

function toggleBlockquote(editor, hiddenInput, state) {
    restoreEditorSelection(editor, state);

    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);

    // If the cursor is already inside a blockquote, convert it back to a paragraph.
    const blockquote = findClosestElement(range.commonAncestorContainer, "BLOCKQUOTE", editor);

    if (blockquote) {
        unwrapBlockquote(blockquote);
        syncEditorToInput(editor, hiddenInput);
        saveEditorSelection(editor, state);
        return;
    }

    // Otherwise convert the current block into a quote.
    document.execCommand("formatBlock", false, "blockquote");

    syncEditorToInput(editor, hiddenInput);
    saveEditorSelection(editor, state);
}

function unwrapBlockquote(blockquote) {
    // Convert the blockquote into a normal paragraph.
    const paragraph = document.createElement("p");
    paragraph.innerHTML = blockquote.innerHTML || "<br>";

    blockquote.replaceWith(paragraph);

    // Move cursor to the end of the new paragraph.
    const range = document.createRange();
    range.selectNodeContents(paragraph);
    range.collapse(false);

    const selection = window.getSelection();

    if (selection) {
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function handleEditorKeydown(event, editor, hiddenInput, state) {
    // Only intercept Enter.
    if (event.key !== "Enter") {
        return;
    }

    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);

    if (!editor.contains(range.commonAncestorContainer)) {
        return;
    }

    // Check whether the cursor is inside a blockquote.
    const blockquote = findClosestElement(range.commonAncestorContainer, "BLOCKQUOTE", editor);

    if (!blockquote) {
        return;
    }

    // Shift + Enter should stay inside the quote as a line break.
    if (event.shiftKey) {
        return;
    }

    // Normal Enter at the end of a quote exits back to a normal paragraph.
    if (isSelectionAtEndOfElement(range, blockquote)) {
        event.preventDefault();

        const paragraph = document.createElement("p");
        paragraph.innerHTML = "<br>";

        blockquote.insertAdjacentElement("afterend", paragraph);

        const newRange = document.createRange();
        newRange.selectNodeContents(paragraph);
        newRange.collapse(true);

        selection.removeAllRanges();
        selection.addRange(newRange);

        state.savedRange = newRange.cloneRange();
        syncEditorToInput(editor, hiddenInput);
    }
}

function findClosestElement(node, tagName, boundary) {
    let current = node;

    // Walk up from the current node until hitting the editor boundary.
    while (current && current !== boundary) {
        if (current.nodeType === Node.ELEMENT_NODE && current.tagName === tagName) {
            return current;
        }

        current = current.parentNode;
    }

    return null;
}

function findClosestElementByTags(node, tagNames, boundary) {
    let current = node;

    // Walk upward until a matching tag is found or the editor boundary is reached.
    while (current && current !== boundary) {
        if (
            current.nodeType === Node.ELEMENT_NODE &&
            tagNames.includes(current.tagName)
        ) {
            return current;
        }

        current = current.parentNode;
    }

    return null;
}

function isSelectionAtEndOfElement(range, element) {
    /**
     * Create a temporary range from the cursor position to the end
     * of the element. If that remaining range is empty, the cursor
     * is at the end.
     */
    const testRange = document.createRange();

    testRange.selectNodeContents(element);
    testRange.setStart(range.endContainer, range.endOffset);

    return testRange.toString().trim() === "";
}