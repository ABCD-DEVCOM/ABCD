<?php

/**
 * Name: UploadRenderer.php
 * Author: Roger C. Guilherme
 * Created: 2026-05-02
 * 
 * Description: Unified modular renderer for Upload fields (Type U) with CSS, JS, and Translations
 */


class UploadRenderer
{
    private static $assetsInjected = false;

    public static function renderInput($Etq, $campo, $rows, $cols, $style_input = "", $maxlength = 0)
    {
        global $msgstr;

        // Converts FDT columns to CSS: If the value is 100 or higher, it is 100%. If it is lower (e.g., 20), it uses the character width ‘ch’.
        $css_width = ($cols >= 100) ? "100%" : $cols . "ch";

        if (empty($style_input)) {
            $style_input = "box-sizing:border-box; padding: 8px; border: none; border-radius: 4px; font-family: inherit; font-size: 13px; width: 100%; resize: vertical; min-height: 40px;";
        }

        echo "<div class='abcd-upload-wrapper' data-tag='tag{$Etq}' style='position:relative; display:flex; flex-direction:column; gap:8px; width: {$css_width}; max-width: 100%; border: 2px dashed #b9d8d9; border-radius: 6px; padding: 4px; background: #fafcfc; transition: all 0.2s ease;'>";

        echo "<div style='display:flex; align-items:center; width:100%;'>";
        if ($rows > 1) {
            echo "<textarea tabindex='0' cols='" . $cols . "' name='tag" . $Etq . "' id='tag" . $Etq . "' rows='" . $rows . "' class='abcd-upload-input' style=\"$style_input\"";
            if ($maxlength > 0) {
                echo " onKeyDown=\"textCounter(document.forma1.tag" . $Etq . ", document.forma1.rem$Etq, $maxlength)\" onKeyUp=\"textCounter(document.forma1.tag" . $Etq . ", document.forma1.rem$Etq, $maxlength)\"";
            }
            echo " placeholder='" . ($msgstr["drag_drop"] ?? "Arraste ficheiros para aqui") . "'>" . htmlspecialchars($campo ?? '', ENT_QUOTES, 'UTF-8') . "</textarea>";
        } else {
            echo "<input tabindex='0' type='text' name='tag" . $Etq . "' id='tag" . $Etq . "' size='" . $cols . "' class='abcd-upload-input' style=\"$style_input\" placeholder='" . ($msgstr["drag_drop"] ?? "Arraste ficheiros para aqui") . "' value=\"" . htmlspecialchars($campo ?? '', ENT_QUOTES, 'UTF-8') . "\">";
        }
        echo "</div>";

        echo "<div style='display:flex; justify-content:flex-end; gap:6px; padding-top:4px; border-top: 1px solid #eee;'>";
        echo "<button type='button' class='abcd-btn-upload' onclick=\"ABCD_openUploadModal('tag$Etq')\" title='" . ($msgstr["uploadfile"] ?? "Enviar Arquivo") . "'>";
        echo "<i class='fas fa-cloud-upload-alt'></i> " . ($msgstr["uploadfile"] ?? "Enviar") . "</button>";
        echo "<button type='button' class='abcd-btn-explore' onclick=\"SelectArchivo('tag$Etq','forma1')\" title='" . ($msgstr["selfile"] ?? "Explorar") . "'>";
        echo "<i class='far fa-folder-open'></i> " . ($msgstr["explore"] ?? "Explorar") . "</button>";
        echo "</div>";

        echo "<div class='abcd-upload-progress' style='display:none; height:4px; background:#e9ecef; border-radius:2px; margin-top:4px; overflow:hidden;'>";
        echo "<div class='abcd-progress-bar' style='height:100%; width:0%; background:#28a745; transition:width 0.2s;'></div>";
        echo "</div>";

        echo "</div>";

        self::injectAssets();
    }

    public static function renderRow($titulo, $ver, $len, $tag, $campo)
    {
        $cols = 100;
        $rows = 1;

        if (is_array($len)) {
            $cols = $len[0] ?? 100;
            $rows = $len[1] ?? 1;
        } elseif (strpos($len, '/') !== false) {
            $parts = explode('/', $len);
            $cols = $parts[0];
            $rows = $parts[1] ?? 1;
        } else {
            $cols = $len ?: 100;
        }

        echo "\n<td class=\"table-fdt-three\">$titulo</td>\n";
        echo "<td class=\"table-fdt-four input-fdt\">";
        self::renderInput($tag, $campo, $rows, $cols);
        echo "</td>\n";
    }

    public static function injectAssets()
    {
        if (self::$assetsInjected) return;
        self::$assetsInjected = true;

        global $msgstr;

        // Translation strings that will be passed to JavaScript
        $str_upload = $msgstr["uploadfile"] ?? "Enviar Arquivo";
        $str_click = $msgstr["click_browse"] ?? "Clique para procurar";
        $str_or_drag = $msgstr["or_drag"] ?? "ou arraste";
        $str_files_here = $msgstr["files_here"] ?? "ficheiro(s) para cá";
        $str_sending = $msgstr["sending"] ?? "Enviando...";
        $str_error_upload = $msgstr["error_upload"] ?? "Erro no Upload";
        $str_error_server = $msgstr["error_server"] ?? "Erro de comunicação com o servidor";

        // INJECTING CSS AND JAVASCRIPT TOGETHER
        echo "
        <style>
            .abcd-upload-wrapper.dragover { background-color: #e8f4f8 !important; border-color: #005aa9 !important; }
            .abcd-upload-input:focus { outline: none; background-color: #fff; }
            .abcd-btn-upload, .abcd-btn-explore { padding: 6px 12px; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s, color 0.2s; }
            .abcd-btn-upload { background-color: #005aa9; color: #fff; }
            .abcd-btn-upload:hover { background-color: #004080; }
            .abcd-btn-explore { background-color: #e2e8f0; color: #495057; }
            .abcd-btn-explore:hover { background-color: #cbd3da; }
            .abcd-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 999999; backdrop-filter: blur(2px); }
            .abcd-modal-content { background: #fff; width: 90%; max-width: 500px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column; overflow: hidden; }
            .abcd-modal-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; }
            .abcd-modal-header h3 { margin: 0; font-size: 16px; color: #333; }
            .abcd-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #999; }
            .abcd-modal-close:hover { color: #dc3545; }
            .abcd-modal-body { padding: 20px; }
            .abcd-modal-dropzone { border: 2px dashed #ccc; border-radius: 6px; padding: 40px 20px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; }
            .abcd-modal-dropzone:hover, .abcd-modal-dropzone.dragover { border-color: #005aa9; background: #e8f4f8; }
            .abcd-modal-dropzone i { font-size: 40px; color: #adb5bd; margin-bottom: 10px; }
            .abcd-modal-dropzone p { margin: 0; color: #6c757d; font-size: 14px; }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            ABCD_initDragAndDrop();
        });

        function ABCD_initDragAndDrop() {
            const wrappers = document.querySelectorAll('.abcd-upload-wrapper');
            wrappers.forEach(wrapper => {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    wrapper.addEventListener(eventName, preventDefaults, false);
                });
                ['dragenter', 'dragover'].forEach(eventName => {
                    wrapper.addEventListener(eventName, () => wrapper.classList.add('dragover'), false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    wrapper.addEventListener(eventName, () => wrapper.classList.remove('dragover'), false);
                });
                wrapper.addEventListener('drop', function(e) {
                    let dt = e.dataTransfer;
                    let files = dt.files;
                    if (files.length > 0) {
                        let tagId = wrapper.getAttribute('data-tag');
                        let targetInput = document.getElementById(tagId);
                        let isMultiple = (targetInput && targetInput.tagName === 'TEXTAREA');
                        
                        let filesArray = [];
                        if (isMultiple) {
                            for(let i=0; i<files.length; i++) filesArray.push(files[i]);
                        } else {
                            filesArray.push(files[0]);
                        }
                        ABCD_uploadFileAjax(filesArray, tagId, wrapper);
                    }
                }, false);
            });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function ABCD_openUploadModal(tagId) {
            let oldModal = document.getElementById('abcdUploadModal');
            if (oldModal) oldModal.remove();

            let targetInput = document.getElementById(tagId);
            let isMultiple = (targetInput && targetInput.tagName === 'TEXTAREA');
            let multipleAttr = isMultiple ? \"multiple\" : \"\";

            let modalHTML = `
                <div id='abcdUploadModal' class='abcd-modal-overlay'>
                    <div class='abcd-modal-content'>
                        <div class='abcd-modal-header'>
                            <h3>{$str_upload}` + (isMultiple ? ' (s)' : '') + `</h3>
                            <button type='button' class='abcd-modal-close' onclick='document.getElementById(\"abcdUploadModal\").remove()'>&times;</button>
                        </div>
                        <div class='abcd-modal-body'>
                            <input type='file' id='abcdFileInput_` + tagId + `' ` + multipleAttr + ` style='display:none' onchange='ABCD_handleFileSelect(this, \"` + tagId + `\")'>
                            <div class='abcd-modal-dropzone' id='abcdDropzone_` + tagId + `' onclick='document.getElementById(\"abcdFileInput_\" + \"` + tagId + `\").click()'>
                                <i class='fas fa-cloud-upload-alt'></i>
                                <p><strong>{$str_click}</strong> {$str_or_drag} ` + (isMultiple ? '{$str_files_here}' : 'um ficheiro') + `</p>
                            </div>
                            <div id='abcdModalProgress_` + tagId + `' style='display:none; margin-top:15px;'>
                                <div style='font-size:12px; margin-bottom:5px; color:#555;' id='abcdModalStatus_` + tagId + `'>{$str_sending}</div>
                                <div style='height:6px; background:#e9ecef; border-radius:3px; overflow:hidden;'>
                                    <div id='abcdModalBar_` + tagId + `' style='height:100%; width:0%; background:#005aa9; transition:width 0.2s;'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            let dropzone = document.getElementById('abcdDropzone_' + tagId);
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, () => dropzone.classList.add('dragover'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, () => dropzone.classList.remove('dragover'), false);
            });
            dropzone.addEventListener('drop', function(e) {
                let dt = e.dataTransfer;
                let files = dt.files;
                if (files.length > 0) {
                    let filesArray = [];
                    if (isMultiple) {
                        for(let i=0; i<files.length; i++) filesArray.push(files[i]);
                    } else {
                        filesArray.push(files[0]);
                    }
                    ABCD_uploadFileAjax(filesArray, tagId, null, true);
                }
            }, false);
        }

        function ABCD_handleFileSelect(input, tagId) {
            if (input.files.length > 0) {
                let targetInput = document.getElementById(tagId);
                let isMultiple = (targetInput && targetInput.tagName === 'TEXTAREA');
                let filesArray = [];
                if (isMultiple) {
                    for(let i=0; i<input.files.length; i++) filesArray.push(input.files[i]);
                } else {
                    filesArray.push(input.files[0]);
                }
                ABCD_uploadFileAjax(filesArray, tagId, null, true);
            }
        }

        function ABCD_uploadFileAjax(filesArray, tagId, wrapperElement = null, isFromModal = false) {
            let baseName = (typeof top.base !== 'undefined') ? top.base : (document.forma1 && document.forma1.base ? document.forma1.base.value : '');
            let storein = (typeof top.img_dir !== 'undefined') ? top.img_dir : '';

            let formData = new FormData();
            for(let i=0; i<filesArray.length; i++) {
                formData.append('userfile[]', filesArray[i]);
            }
            formData.append('base', baseName);
            formData.append('storein', storein);
            formData.append('ajax_upload', '1'); 

            let progressBar, statusText;
            
            if (isFromModal) {
                document.getElementById('abcdModalProgress_' + tagId).style.display = 'block';
                document.getElementById('abcdDropzone_' + tagId).style.display = 'none';
                progressBar = document.getElementById('abcdModalBar_' + tagId);
                statusText = document.getElementById('abcdModalStatus_' + tagId);
            } else if (wrapperElement) {
                let progressContainer = wrapperElement.querySelector('.abcd-upload-progress');
                progressContainer.style.display = 'block';
                progressBar = progressContainer.querySelector('.abcd-progress-bar');
            }

            let xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_img.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    let percentComplete = (e.loaded / e.total) * 100;
                    if (progressBar) progressBar.style.width = percentComplete + '%';
                    if (statusText) statusText.innerText = '{$str_sending} ' + Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status == 200) {
                    try {
                        let response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            let inputField = document.getElementById(tagId);
                            if (inputField) {
                            if (inputField.tagName === 'TEXTAREA') {
                                    let newNames = response.filenames.join('\\n');
                                    if (inputField.value.trim() !== '') {
                                        inputField.value += '\\n' + newNames;
                                    } else {
                                        inputField.value = newNames;
                                    }
                                } else {
                                    inputField.value = response.filenames[0];
                                }
                                
                                inputField.dispatchEvent(new Event('change', { bubbles: true }));
                                
                                if (isFromModal) {
                                    setTimeout(() => document.getElementById('abcdUploadModal').remove(), 500);
                                } else if (wrapperElement) {
                                    setTimeout(() => {
                                        wrapperElement.querySelector('.abcd-upload-progress').style.display = 'none';
                                        progressBar.style.width = '0%';
                                    }, 1000);
                                }
                            }
                        } else {
                            alert('{$str_error_upload}: ' + (response.message || 'Falha'));
                            if (isFromModal) {
                                document.getElementById('abcdDropzone_' + tagId).style.display = 'block';
                                document.getElementById('abcdModalProgress_' + tagId).style.display = 'none';
                            }
                        }
                    } catch(e) {
                        alert('Resposta inválida do servidor. Verifique a caixa de texto.');
                    }
                } else {
                    alert('{$str_error_server}');
                }
            };
            xhr.send(formData);
        }
        </script>";
    }
}
