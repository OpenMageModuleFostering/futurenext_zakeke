/*******************************************************
 * Copyright (C) 2016 FutureNext SRL
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of FutureNext SRL
 *******************************************************/

function zakekeShowPreview(preview) {
    var previewUrl = preview.lastElementChild.src,
        previewLabel = preview.dataset.zakekeLabel,
        backdrop = window.document.createElement('DIV'),
        popup = window.document.createElement('DIV'),
        content = window.document.createElement('DIV'),
        label = window.document.createElement('DIV'),
        labelText = window.document.createTextNode(previewLabel),
        image = window.document.createElement('IMG');

    content.style = 'position: relative';

    if (previewLabel && previewLabel.length > 0) {
        label.appendChild(labelText);
        label.style.cssText = 'position: absolute; bottom: 0px; left: 0px; width: 100%; padding: 10px; box-sizing: border-box; color: rgb(51, 51, 51); background: rgba(255, 255, 255, 0.87) none repeat scroll 0% 0%; border-top: 1px solid rgb(255, 255, 255); border-bottom: 1px solid rgba(0, 0, 0, 0.25); border-radius: 0px 0px 5px 5px; box-shadow: 0px -2px 4px rgba(0, 0, 0, 0.35); font-weight: bold;';

        content.appendChild(label);
    }

    image.src = previewUrl;

    popup.style.cssText = 'align-self: center; background: white';
    content.appendChild(image);

    popup.appendChild(content);

    backdrop.appendChild(popup);

    backdrop.addEventListener('click', function () {
        preview.parentElement.parentElement.removeChild(backdrop);
    });

    backdrop.style.cssText = 'position: fixed; height: 100%; width: 100%; background: black; left: 0; top: 0; z-index: 9999; display: flex; flex-direction: column; justify-content: center;';

    preview.parentElement.parentElement.appendChild(backdrop);
}