/*
20210604 gascensio Added CruzarABCD
20251210 rogercgui Reescreveu a função SendTo para melhorar a lógica de popup e submissão de formulários, além de adicionar suporte para seleção de formato no modal.
*/
accion=""

function showhide(what, what2) {
	var el = document.getElementById(what);
	if (el.style.display == "none") {
		el.style.display = "inline";
		if (arguments.length > 1) what2.src = "assets/images/buttonm.gif";
	} else {
		el.style.display = "none";
		if (arguments.length > 1) what2.src = "assets/images/buttonp.gif";
	}
}

function ProximaPagina(pagina, registro) {
	document.continuar.desde.value = registro;
	document.continuar.pagina.value = pagina;
	document.continuar.submit();
}

function BuscarBase(base) {
	document.buscar.action = "buscar_integrada.php";
	document.buscar.base.value = base;
	document.buscar.desde.value = 1;
	document.buscar.count.value = 25;
	document.buscar.resaltar.value = "S";
	document.buscar.Expresion.value = Expresion;
	document.buscar.Opcion.value = "integrada";
	document.buscar.submit();
}

function ProximaBase(base) {
	document.buscar.action = "buscar_integrada.php";
	document.buscar.desde.value = 1;
	document.buscar.base.value = base;
	document.buscar.pagina.value = 1;
	document.buscar.Formato.value = "";
	document.buscar.submit();
}

function CambiarFormato() {
	var Ctrl = document.getElementById("cambio_Pft");
	var ix = Ctrl.selectedIndex;
	var Formato = Ctrl.options[ix].value;
	document.continuar.Formato.value = Formato;
	document.continuar.desde.value = 1;
	document.continuar.submit();
}

function AbrirIndice(Letra) {
	document.diccionario.IR_A.value = Letra;
	NavegarDiccionario(this, 3);
}

function LimpiarBusqueda() {
	// Limpa todos os inputs de texto da busca detalhada
	var inputs = document.getElementsByName("Sub_Expresiones[]");
	for (var i = 0; i < inputs.length; i++) {
		inputs[i].value = "";
	}
}

// ============================================================================
// LÓGICA DE DICIONÁRIO E BUSCA DETALHADA
// ============================================================================

function ArmarBusqueda() {
	Expresion = ""
	Operadores = ""
	Campos = ""

	// Seleciona os elementos corretamente pelo nome com []
	var inputs = document.getElementsByName("Sub_Expresion[]");
	var selects = document.getElementsByName("camp[]");
	var opers = document.getElementsByName("oper[]"); // Se houver selects de operadores

	// Loop baseado na quantidade de inputs de texto encontrados
	for (var i = 0; i < inputs.length; i++) {

		// Valor do Texto
		var val = inputs[i].value;
		if (val == "") val = " "; // Mantém espaço para não quebrar a lógica do ABCD

		if (Expresion == "") {
			Expresion = val + " ~~~ ";
		} else {
			Expresion = Expresion + val + " ~~~ ";
		}

		// Valor do Campo (Select)
		var campVal = "";
		if (selects[i]) {
			campVal = selects[i].value;
		}

		if (Campos == "") {
			Campos = campVal;
		} else {
			Campos = Campos + " ~~~ " + campVal;
		}

		// Valor do Operador (Select)
		// Nota: O loop vai até length, mas operadores são sempre length-1
		if (i < inputs.length - 1) {
			var operVal = "and"; // Default
			if (opers[i]) {
				operVal = opers[i].value;
			}

			if (Operadores == "") {
				Operadores = operVal;
			} else {
				Operadores = Operadores + " ~~~ " + operVal;
			}
		}
	}
}

// Substitua as funções Diccionario e ArmarBusqueda por estas:

function Diccionario(jx) {
	ArmarBusqueda();

	// 1. Identifica o campo e o prefixo
	var selects = document.getElementsByName("camp[]");
	if (!selects[jx]) selects = document.getElementsByName("camp"); // Fallback

	if (!selects[jx]) {
		alert("Erro: Campo de índice " + jx + " não encontrado.");
		return;
	}

	// Tenta pegar do array dt (PHP) ou do select direto
	var nombrec = "";
	var prefijo = "";

	if (typeof dt !== 'undefined' && dt[jx]) {
		var a = dt[jx];
		var partes = a.split('|');
		nombrec = partes[0];
		prefijo = partes[1];
	} else {
		nombrec = selects[jx].options[selects[jx].selectedIndex].text;
		prefijo = selects[jx].value;
	}

	// 2. Prepara o formulário fantasma
	var formDiccio = document.forms['diccio'];
	formDiccio.campo.value = nombrec;
	formDiccio.prefijo.value = prefijo;
	formDiccio.Diccio.value = jx;

	// Injeta Contexto se necessário
	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		if (!formDiccio.ctx) {
			var inputCtx = document.createElement("input");
			inputCtx.type = "hidden";
			inputCtx.name = "ctx";
			formDiccio.appendChild(inputCtx);
		}
		formDiccio.ctx.value = OpacContext;
	}

	// 3. [MUDANÇA] Abre o Modal Bootstrap em vez de Window.Open
	var modalElement = document.getElementById('diccionarioModal');

	if (modalElement) {
		// Se o modal existe, usa ele (Mobile Friendly)
		var modal = new bootstrap.Modal(modalElement);
		modal.show();

		// Define o alvo do formulário para o IFRAME dentro do modal
		formDiccio.target = "diccionarioIframe";
	} else {
		// Fallback: Se por algum motivo o modal não existir, abre popup antigo
		var w = 800; var h = 600;
		var left = (screen.width / 2) - (w / 2);
		var top = (screen.height / 2) - (h / 2);
		window.open('', 'diccionarioWin', 'width=' + w + ', height=' + h + ', top=' + top + ', left=' + left + ', scrollbars=yes, resizable=yes');
		formDiccio.target = "diccionarioWin";
	}

	// Envia o formulário
	formDiccio.action = "diccionario_integrado.php";
	formDiccio.submit();
}

function ArmarBusqueda() {
	Expresion = ""
	Operadores = ""
	Campos = ""

	// Seleciona os elementos corretamente pelo nome com []
	var inputs = document.getElementsByName("Sub_Expresiones[]");
	var selects = document.getElementsByName("camp[]");
	var opers = document.getElementsByName("oper[]"); // Se houver selects de operadores

	// Loop baseado na quantidade de inputs de texto encontrados
	for (var i = 0; i < inputs.length; i++) {

		// Valor do Texto
		var val = inputs[i].value;
		if (val == "") val = " "; // Mantém espaço para não quebrar a lógica do ABCD

		if (Expresion == "") {
			Expresion = val + " ~~~ ";
		} else {
			Expresion = Expresion + val + " ~~~ ";
		}

		// Valor do Campo (Select)
		var campVal = "";
		if (selects[i]) {
			campVal = selects[i].value;
		}

		if (Campos == "") {
			Campos = campVal;
		} else {
			Campos = Campos + " ~~~ " + campVal;
		}

		// Valor do Operador (Select)
		// Nota: O loop vai até length, mas operadores são sempre length-1
		if (i < inputs.length - 1) {
			var operVal = "and"; // Default
			if (opers[i]) {
				operVal = opers[i].value;
			}

			if (Operadores == "") {
				Operadores = operVal;
			} else {
				Operadores = Operadores + " ~~~ " + operVal;
			}
		}
	}
}


function ObtenerTerminos(desde) {
    var Expresion = "";
    // Tenta localizar o formulário de forma robusta
    var form = document.forms['diccionario'];
    if (!form && document.getElementById('diccionario')) form = document.getElementById('diccionario');
    
    if (!form) return "";
    
    var Ctrl = form.elements[desde];
    if (!Ctrl) return "";

    var delimitador = '"';
    var ix = Ctrl.options.length;

    for (var i = 0; i < ix; i++) {
        // [CORREÇÃO CRÍTICA]: 
        // Se a origem for 'TerminosSeleccionados' (caixa da direita), pegamos TODOS os itens.
        // Se for outra (caixa da esquerda), pegamos apenas os clicados (.selected).
        if (desde == "TerminosSeleccionados" || Ctrl.options[i].selected) {
            if (Expresion == "")
                Expresion = delimitador + Ctrl.options[i].value + delimitador;
            else
                Expresion += " " + delimitador + Ctrl.options[i].value + delimitador;
        }
    }
    return Expresion;
}

function EjecutarBusquedaDiccionario(Accion) {
	var Seleccionados = ObtenerTerminos("TerminosSeleccionados");

	if (Seleccionados == "") {
		alert("Por favor, selecione ao menos um termo na lista.");
		return false;
	}

	Seleccionados = Seleccionados.replace(/""/g, '"');

	var form = document.forms['diccionario'];
	if (!form && document.getElementById('diccionario')) form = document.getElementById('diccionario');

	form.Seleccionados.value = Seleccionados;

	switch (Accion) {
		case 0: // Buscar Direto
			// 1. Identifica o prefixo do campo e o operador lógico
			var prefijo = form.prefijo ? form.prefijo.value : "";
			var operador = "and";

			if (form.alcance) {
				if (form.alcance.length && form.alcance[0].type === 'radio') {
					for (var r = 0; r < form.alcance.length; r++) {
						if (form.alcance[r].checked) {
							operador = form.alcance[r].value;
							break;
						}
					}
				} else if (form.alcance.value) {
					operador = form.alcance.value;
				}
			}
			if (!operador) operador = "and";

			// 2. Quebra os múltiplos termos selecionados e constrói a expressão WXIS correta
			var terminosArray = Seleccionados.match(/(?:[^\s"]+|"[^"]*")+/g);
			var expresionFinal = "";

			if (terminosArray && terminosArray.length > 0) {
				for (var j = 0; j < terminosArray.length; j++) {
					var termoLimpo = terminosArray[j].replace(/"/g, ''); // Remove aspas
					var part = "(" + prefijo + termoLimpo + ")";

					if (expresionFinal === "") {
						expresionFinal = part;
					} else {
						expresionFinal += " " + operador + " " + part;
					}
				}
			}

			// 3. Prepara os dados invisíveis para o envio
			form.Opcion.value = "directa";
			form.Sub_Expresion.value = Seleccionados;

			// Garante que o input Expresion exista e receba a query mastigada (ex: (TW_ACADEMIA) and (TW_BRASIL))
			if (!form.Expresion) {
				var inputExp = document.createElement("input");
				inputExp.type = "hidden";
				inputExp.name = "Expresion";
				form.appendChild(inputExp);
			}
			form.Expresion.value = expresionFinal;

			// 4. Dispara a URL final na janela pai
			if (window.parent && window.frameElement) {
				var url = "./?page=startsearch&Opcion=directa&base=" + encodeURIComponent(form.base.value) + "&Expresion=" + encodeURIComponent(expresionFinal);
				if (form.ctx && form.ctx.value !== "") url += "&ctx=" + encodeURIComponent(form.ctx.value);
				if (typeof OpacLang !== 'undefined') url += "&lang=" + encodeURIComponent(OpacLang);

				window.parent.location.href = url;
			} else {
				// Se for popup nativo (sem iframe)
				form.action = "./";
				form.submit();
			}
			break;

		case 1:
			form.action = "buscar_integrada.php";
			form.submit();
			break;

		case 2: // Enviar para Formulário
			var jx = form.Diccio.value;

			// [MUDANÇA] Define quem é a janela alvo (Pai ou Opener)
			var targetWindow = null;
			if (window.opener && !window.opener.closed) targetWindow = window.opener; // Popup
			else if (window.parent && window.frameElement) targetWindow = window.parent; // Iframe/Modal

			if (targetWindow) {
				var parentDoc = targetWindow.document;

				// Busca os inputs na janela alvo
				var parentInputs = parentDoc.getElementsByName("Sub_Expresiones[]");
				if (parentInputs.length == 0) parentInputs = parentDoc.getElementsByName("Sub_Expresiones");

				if (parentInputs[jx]) {
					var valorAtual = parentInputs[jx].value;
					if (valorAtual != "") {
						parentInputs[jx].value = valorAtual + " " + Seleccionados;
					} else {
						parentInputs[jx].value = Seleccionados;
					}

					// Fecha a janela correta
					if (window.opener) window.close(); // Popup
					else {
						// Fecha o Modal do Bootstrap na janela pai
						var modalEl = parentDoc.getElementById('diccionarioModal');
						if (modalEl) {
							var modalInstance = targetWindow.bootstrap.Modal.getInstance(modalEl);
							if (modalInstance) modalInstance.hide();
						}
					}
				} else {
					alert("Erro: Campo de destino não encontrado.");
				}
			} else {
				alert("Erro: A janela principal foi perdida.");
			}
			break;
	}
}

function NavegarDiccionario(F, desde) {
	var Seleccionados = ObtenerTerminos("TerminosSeleccionados");
	if (Seleccionados != "") {
		document.diccionario.Seleccionados.value = Seleccionados;
	}

	switch (desde) {
		case 4: // Mais termos
			document.diccionario.Navegacion.value = "mas terminos";
			document.diccionario.submit(); // Submete para a mesma página (diccionario_integrado.php)
			break;
		case 3: // Ir a
			document.diccionario.Navegacion.value = "ir a";
			document.diccionario.LastKey.value = document.diccionario.IR_A.value;
			document.diccionario.submit(); // Submete para a mesma página
			break;
	}
}

function ArmarExpresionBusqueda() {
	var inputs = document.getElementsByName("Sub_Expresiones[]");
	var selects = document.getElementsByName("camp[]");
	var opers = document.getElementsByName("oper[]");

	// Fallback para nomes sem colchetes se necessário
	if (inputs.length == 0) {
		inputs = document.getElementsByName("Sub_Expresiones");
		selects = document.getElementsByName("camp");
		opers = document.getElementsByName("oper");
	}

	var ExpresionFinal = "";

	for (var i = 0; i < inputs.length; i++) {
		var valor = inputs[i].value.trim();
		var prefijo = selects[i] ? selects[i].value : ""; // Ex: TI_

		if (valor !== "") {
			// CORREÇÃO: Pega múltiplos termos separados por espaço (respeitando aspas)
			var termosArray = valor.match(/(?:[^\s"]+|"[^"]*")+/g);
			var subExpresion = "";

			if (termosArray && termosArray.length > 0) {
				for (var j = 0; j < termosArray.length; j++) {
					var termoLimpo = termosArray[j].replace(/"/g, ''); // Remove aspas
					var part = "(" + prefijo + termoLimpo + ")";
					
					// Une os termos do mesmo campo com AND
					if (subExpresion === "") {
						subExpresion = part;
					} else {
						subExpresion += " and " + part; 
					}
				}
			}

			// Se houver mais de um termo no campo, envelopa tudo em parênteses
			var termoMontado = termosArray.length > 1 ? "(" + subExpresion + ")" : subExpresion;

			// Adiciona o operador que conecta com a linha anterior
			if (ExpresionFinal !== "") {
				var operador = "and"; // Default
				if (i > 0 && opers[i - 1]) {
					operador = opers[i - 1].value;
				}
				ExpresionFinal += " " + operador + " " + termoMontado;
			} else {
				ExpresionFinal = termoMontado;
			}
		}
	}
	return ExpresionFinal;
}

function PrepararExpresion() {
	// 1. Monta a string de busca correta
	var expresionBooleana = ArmarExpresionBusqueda();

	if (expresionBooleana === "") {
		alert(msgstr["miss_se"] || "Digite pelo menos um termo para pesquisar.");
		return;
	}

	// 2. Localiza o formulário principal de busca (forma1)
	var forma1 = document.forms['forma1'];
	if (!forma1 && document.getElementById('forma1')) forma1 = document.getElementById('forma1');

	if (!forma1) {
		alert("Erro crítico: Formulário de busca não encontrado.");
		return;
	}

	// 3. Preenche os campos para o backend
	forma1.Expresion.value = expresionBooleana;
	forma1.Opcion.value = "directa"; // Define explicitamente como busca direta

	// Debug (Opcional - pode remover depois)
	// console.log("Enviando Busca:", expresionBooleana);

	// 4. Mostra loading
	var mensajes = document.getElementById("mensajes");
	if (mensajes) mensajes.innerHTML = "<img src=assets/img/loading.gif>";

	// 5. Envia
	forma1.submit();
}

function PrepararExpresionExperta() {
	var textarea = document.getElementById('expert_search_query');

	if (!textarea || textarea.value.trim() === "") {
		alert(msgstr["miss_se"] || "Digite uma expressão de busca booleana válida.");
		return;
	}

	var forma1 = document.forms['forma1'];
	if (!forma1 && document.getElementById('forma1')) {
		forma1 = document.getElementById('forma1');
	}

	if (!forma1) {
		alert("Erro crítico: Formulário de busca não encontrado.");
		return;
	}

	// Injeta a string crua da textarea direto no campo Expresion
	forma1.Expresion.value = textarea.value.trim();
	forma1.Opcion.value = "directa"; // Garante que o WXIS leia a query literalmente

	var mensagens = document.getElementById("mensajes");
	if (mensagens) mensagens.innerHTML = "<img src='assets/img/loading.gif'>";

	forma1.submit();
}

function CancelarDiccionario(retorno) {
	// Apenas fecha o popup
	window.close();
}

function CancelarDiccionario(retorno){
	switch (retorno){
		case 'A':
			document.diccionario.action="avanzada.php"
			document.diccionario.submit()
			break
		case 'B':
			document.diccionario.action="otras_busquedas.php"
			document.diccionario.submit()
			break;
		case 'C':
			document.diccionario.action="avanzada.php"
			document.diccionario.submit()
			break
		case 'D':
			document.diccionario.action="buscar_integrada.php"
			document.diccionario.submit()
			break
		default:
			document.diccionario.action=retorno
			document.diccionario.submit()
	}
}

// ============================================================================
// FUNÇÕES DE ENVIO E EXPORTAÇÃO (SendTo)
// ============================================================================


function SendTo(Accion, Data) {
	let base = '';
	let mfn = '';
	let cookieData = '';
	let isSingleRecordAction = false;
	const accionLower = Accion.toLowerCase();

	// 1. Tenta extrair base e MFN (sem zeros) do ID (c_base_mfn)
	// Isso identifica uma ação individual (ex: clique no botão do modal)
	if (Data && typeof Data === 'string' && Data.startsWith('c_=')) {
		const parts = Data.substring(3).split('_=');
		if (parts.length >= 2) {
			base = parts[0];
			mfn = parseInt(parts[1], 10).toString();
			isSingleRecordAction = true;
			cookieData = Data; // Guarda o ID original para usar como 'cookie'
		}
	}

	// 2. Fallback para Cookie (se Data não for ID válido)
	// Isso identifica uma ação em múltiplos registros (ex: barra de seleção)
	if (!isSingleRecordAction) {
		cookieData = getCookie('ABCD');
		if (!cookieData) {
			alert(msgstr["sel_reg"]); // Alerta "Selecione um registro"
			return;
		}
		// Tenta pegar a base do primeiro item do cookie
		const firstItem = cookieData.split('|')[0];
		if (firstItem && firstItem.startsWith('c_')) {
			const parts = firstItem.substring(2).split('_');
			if (parts.length >= 2) {
				base = parts[0]; // Pega a base do primeiro item
			}
		}
		if (!base) {
			alert("Erro: Não foi possível determinar a base de dados a partir da seleção.");
			return;
		}
	}

	// 3. Obter Formato (Apenas para ações de popup)
	// Tenta pegar o formato do seletor do modal, se existir
	let selectedFormat = '';
	const modalFormatSelector = document.getElementById('modalFormatSelectorContainer');
	if (modalFormatSelector) {
		const activeButton = modalFormatSelector.querySelector('.format-button.active');
		if (activeButton) {
			selectedFormat = activeButton.dataset.format;
		}
	}

	// 4. DECISÃO DE FLUXO (Popup vs. Submissão de Formulário)
	let scriptName = '';
	let url = '';

	switch (accionLower) {
		// --- FLUXO 1: Ações que abrem POPUP/NOVA JANELA ---
		case 'print':
		case 'iso':
		case 'word':
		case 'xml':
		case 'download_xml':

			// Define o script PHP com base na ação
			if (accionLower === 'print') scriptName = 'sendtoprint.php';
			if (accionLower === 'iso') scriptName = 'sendtoiso.php';
			if (accionLower === 'word') scriptName = 'sendtoword.php';
			if (accionLower === 'xml' || accionLower === 'download_xml') scriptName = 'sendtoxml.php';

			url = `components/${scriptName}?base=${encodeURIComponent(base)}`;

			// Passa o(s) identificador(es) via parâmetro 'cookie'
			if (cookieData) {
				url += `&cookie=${encodeURIComponent(cookieData)}`;
			}

			// Adiciona o Formato SE ele foi determinado
			if (selectedFormat) {
				url += `&Formato=${encodeURIComponent(selectedFormat)}`;
			}

			// Adiciona o Idioma
			if (typeof OpacLang !== 'undefined') {
				url += `&lang=${encodeURIComponent(OpacLang)}`;
			}

			if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
				url += `&ctx=${encodeURIComponent(OpacContext)}`;
			}

			console.log("SendTo (Popup): URL final:", url);
			window.open(url, Accion);
			break;

		case 'reserve':
		case 'reserve_one': // Captura os dois
			// Lógica recuperada da função antiga
			if (typeof WEBRESERVATION !== 'undefined' && WEBRESERVATION !== "Y") {
				alert(msgstr["reserv_no"]);
				return;
			}
			if (isSingleRecordAction && base && mfn) {
				// Chama a função que abre o modal de DETALHES
				ExecutarReserva(base, mfn);
				console.log("SendTo (Reserva Individual):", Accion, base, mfn);
			} else {
				// Se for uma ação em múltiplos (cookie), não podemos abrir um modal só
				// Por enquanto, disparamos o fluxo antigo de submissão
				// (Podemos melhorar isso depois)
				if (!document.buscar) {
					console.error("SendTo: Formulário 'document.buscar' não encontrado.");
					return;
				}
				document.buscar.action = "index.php"; // (Ou buscar_integrada.php, como estava antes)
				document.buscar.Accion.value = Accion;
				document.buscar.cookie.value = cookieData;
				document.buscar.submit();
			}
			break;
		// Propositalmente continua para o 'default' para submeter o form

		case 'email':
		case 'bookmark': // Ação do seu .tab
		case 'copy':     // Ação do seu .tab

			// Verifica se o formulário 'buscar' existe
			if (!document.buscar) {
				console.error("SendTo: Formulário 'document.buscar' não encontrado.");
				alert("Erro: Formulário de busca não encontrado.");
				return;
			}

			console.log("SendTo (Submit):", Accion, cookieData);

			// Lógica recuperada da função antiga
			document.buscar.action = "index.php"; // Submete para a home (que deve ler a Ação)
			document.buscar.Accion.value = Accion; // Passa a ação (ex: 'reserve', 'email')
			document.buscar.cookie.value = cookieData; // Passa os MFNs
			document.buscar.submit();
			break;

		// --- Ações desconhecidas ---
		default:
			console.error("SendTo: Ação desconhecida:", Accion);
			alert("Ação não suportada: " + Accion);
			return;
	}
}


// ===============================================
// FUNÇÃO PARA BAIXAR XML DIRETAMENTE
// ===============================================
function downloadXml(base, mfn) {
	if (!base || !mfn) {
		console.error("downloadXml: Base ou MFN faltando.");
		return;
	}

	// Constrói a URL para o script sendtoxml.php
	// Adiciona um parâmetro 'download=true' para garantir que ele force o download
	// Certifique-se que OpacLang está definida globalmente
	let url = `components/sendtoxml.php?base=${encodeURIComponent(base)}&Mfn=${encodeURIComponent(mfn)}&lang=${encodeURIComponent(OpacLang)}&download=true`;

	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		url += `&ctx=${encodeURIComponent(OpacContext)}`;
	}

	console.log("downloadXml: Abrindo URL:", url);
	window.open(url, '_blank'); // Abre em nova aba/janela para iniciar o download
}
// ===============================================

/** BUTTONS **/

function SendToWord() {
	document.regresar.action = "components/sendtoword.php"
	document.regresar.target = ""
	document.regresar.submit()
	document.regresar.action = "./"
}

function SendToXML(seleccion) {
	cookie = document.regresar.cookie.value
	document.regresar.cookie.value = seleccion
	document.regresar.action = "components/sendtoxml.php"
	document.regresar.target = "_blank"
	document.regresar.submit()
	document.regresar.action = "./"
	document.regresar.target = ""
	document.regresar.cookie = cookie
}

function SendToISO() {
	document.regresar.action = "components/sendtoiso.php"
	document.regresar.cookie = cookie
	document.regresar.submit()
	document.regresar.action = "./"
	document.regresar.target = ""

}

function EnviarCorreo() {
	hayerror = 0
	document.correo.comentario.value = escape(document.correo.comentario.value)
	if (Trim(document.correo.email.value) == '' || document.correo.email.value.indexOf('@') == -1) {
		alert('Correo inválido')
		hayerror = 1
	}

	if (hayerror == 1) {
		return false
	} else {
		document.correo.submit()
	}
}

function SendToPrint() {
	cookie = document.regresar.cookie.value
	window.open("components/sendtoprint.php?cookie=" + cookie, "", "width=600, height=600, resizable, scrollbars");

	//cookie = document.regresar.cookie.value
	//document.regresar.cookie.value = seleccion
	//document.regresar.action = "components/sendtoprint.php"
	//document.regresar.target = "_blank"
	//document.regresar.submit()
}

function BuscarPalabrasSide(){
	if (Trim(document.side.Expresion.value)=="")
		return
	document.side.submit()
}

function BuscarPalabrasTope(){
	if (Trim(document.SearchTope.Expresion.value)=="")
		return
	document.SearchTope.submit()
}

function Buscar(Ctrl){
	ix=Ctrl.selectedIndex
	document.buscar.Expresion.value=Ctrl.options[ix].value
	document.buscar.desde.value=1
	document.buscar.action="buscar_integrada.php"
	document.buscar.submit()
	Ctrl.selectedIndex=0
}

function CRUZARD(Prefijo, Termino, base) {
	// Apenas redireciona para a função principal para manter compatibilidade com PFTs muito antigos
	CruzarABCD(Termino, Prefijo);
}

function CruzarABCD(Termino, Prefijo) {
	// 1. Limpa espaços extras e remove aspas soltas que possam quebrar a sintaxe do CISIS
	Termino = Termino.trim().replace(/"/g, '');

	// 2. Constrói a expressão booleana exata para o WXIS. Ex: (AU_Machado de Assis)
	var novaExpressao = "(" + Prefijo + Termino + ")";

	// 3. Pega a URL atual base limpa (ex: /opac/ ou /opac/index.php) preservando o .htaccess
	var url = new URL(window.location.origin + window.location.pathname);

	// 4. Injeta os parâmetros da nova pesquisa
	url.searchParams.set('page', 'startsearch');
	url.searchParams.set('Expresion', novaExpressao);
	url.searchParams.set('Opcion', 'directa'); // Aciona o motor correto para gerar as facetas
	url.searchParams.set('desde', '1');
	url.searchParams.set('pagina', '1');
	url.searchParams.set('resaltar', 'S');

	// 5. Preserva a base de dados atual para não perder o escopo da pesquisa
	var currentParams = new URLSearchParams(window.location.search);
	if (currentParams.has('base')) {
		url.searchParams.set('base', currentParams.get('base'));
	}

	// 6. Preserva o idioma
	if (typeof OpacLang !== 'undefined') {
		url.searchParams.set('lang', OpacLang);
	} else if (currentParams.has('lang')) {
		url.searchParams.set('lang', currentParams.get('lang'));
	}

	// 7. Preserva o contexto (multi-bibliotecas), se existir
	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		url.searchParams.set('ctx', OpacContext);
	} else if (currentParams.has('ctx')) {
		url.searchParams.set('ctx', currentParams.get('ctx'));
	}

	// 8. Dispara a pesquisa com uma URL limpa (o .htaccess assumirá o roteamento interno)
	window.location.href = url.toString();
}

//BUSQUEDA AVANZADA

Expresion=""
Operadores=""
Campos=""

function BusquedaAvanzada(){
	document.diccio.action="avanzada.php"
    document.diccio.Opcion.value="integrada"
	document.diccio.submit()
}


function DiccionarioLibre(Nivel){
	ix=document.getElementById(document.libre.coleccion)
	if (ix!=null){
		Ctrl=document.libre.coleccion
		ixc=Ctrl.length
		colec=""
		if (ixc){
			for (i=0;i<ixc;i++){
				if (Ctrl[i].checked){
					colec=Ctrl[i].value
					break
				}
			}
		}

		if (colec!=""){
			document.diccio_libre.coleccion.value=colec
		}
	}

	if (document.getElementById('and').checked)
		document.diccio_libre.alcance.value=document.getElementById('and').value
	else
		document.diccio_libre.alcance.value=document.getElementById('or').value
	document.diccio_libre.submit()
}


//GALERIA DE IMAGENES

function Presentacion(base,Expresion,Pagina,Formato){
	if (document.getElementById("desde")){
		desde=document.continuar.desde.value-document.continuar.count.value
		if (desde<=0) desde=1
		document.buscar.desde.value=desde
	}
	document.buscar.base.value=base
	document.buscar.pagina.value=Pagina
	switch (Formato){
		case "galeria":
			document.buscar.action="slide_integrada.php";
			break
		case "ficha":
			document.buscar.action="buscar_integrada.php";
			break
	}
	//document.buscar.Opcion.value="integrada"
	document.buscar.Expresion.value=Expresion
	document.buscar.submit()

}

var i = 0;
iactual=0;
var image = new Array();
var link= new Array();
var titulo= new Array()
var ficha=new Array()

var k = image.length-1;

/*
function ProximaImagen(){
	iactual=iactual+1
	if (iactual>=image.length) iactual=0
	swapImage(iactual)

}

function AnteriorImagen(){
	iactual=iactual-1
	if (iactual<0) iactual=0
	swapImage(iactual)

}

function swapImage(i){
	iactual=i;
	var el = document.getElementById("mydiv");
	el.innerHTML=titulo[i];
	var img = document.getElementById("slide");
	img.src= image[i];
	var a = document.getElementById("link");
	a.href= link[i];
	var gal = document.getElementById("galeria");
	gal.innerHTML=ficha[i];
}
*/

function addLoadEvent(func) {
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else  {
		window.onload = function() {
			if (oldonload) {
				oldonload();
			}
			func();
		}
	}
}

addLoadEvent(function() {
		//swapImage(0);
	}
);

function ActivarIndice(titulo,Opcion,count,posting,prefijo,base){
	document.activarindice.titulo.value=titulo
	document.activarindice.Opcion.value=Opcion
	document.activarindice.count.value=count
	document.activarindice.posting.value=posting
	document.activarindice.prefijo.value=prefijo
	document.activarindice.base.value=base
	document.activarindice.submit()
}

function ValidarUsuario(){
	if (Trim(document.estado_de_cuenta.usuario.value)==""){
		alert("Debe ingresar su código de usuario")
		return
	}
	document.estado_de_cuenta.submit()
}



function ChangeLanguage() {
	var langSelect = document.getElementById("lang");
	var langcode = langSelect.options[langSelect.selectedIndex].value;
	var actualScript = window.location.pathname;

	// Atualiza todos os formulários que tenham campo "lang"
	for (var i = 0; i < document.forms.length; i++) {
		if (document.forms[i].elements["lang"]) {
			document.forms[i].elements["lang"].value = langcode;
		}
	}

	// Verifica se o formulário changelanguage existe
	if (document.forms["changelanguage"]) {
		document.forms["changelanguage"].elements["lang"].value = langcode;
		document.forms["changelanguage"].action = actualScript;
		document.forms["changelanguage"].submit();
		console.log("The OPAC is in language: " + langcode);
	} else {
		console.warn("Formulário 'changelanguage' não encontrado.");
	}
}

/*
function openNavFacetas() {
  document.getElementById("SidenavFacetas").style.width = "200px";
}

function closeNavFacetas() {
  document.getElementById("SidenavFacetas").style.width = "0";
}
*/

function closeNav() {
    document.getElementById("sidebar").style.width = "0";
    document.getElementById("sidebar").style.marginLeft = "-30px";
    document.getElementById("page").style.marginLeft = "0px"
    document.getElementById("page").style.width = "95%";
}

function Facetas(Expresion){
	document.buscar.facetas.value=Expresion
	document.buscar.submit()
}

function SolicitarPrestamo(CN,Base,otro){
	document.buscar.action="../prestamos/prestamos.php"
	document.buscar.cookie.value=CN
	document.buscar.submit()

}


/* =============================================== */
/* INÍCIO - LÓGICA DO MODAL DE RESERVA (AJAX)      */
/* =============================================== */

// 1. Função para ABRIR o modal de confirmação
// (Chamada pelo 'SendTo' quando o usuário está logado)
function abrirModalReserva(base, mfn) {
	// Pega as instâncias dos elementos do modal
	const modalElement = document.getElementById('reserveConfirmModal');
	if (!modalElement) {
		console.error("Modal #reserveConfirmModal não encontrado.");
		return;
	}
	const modal = new bootstrap.Modal(modalElement);

	const modalBody = document.getElementById('reserveModalBodyContent');
	const modalLoading = document.getElementById('reserveModalLoading');
	const modalFeedback = document.getElementById('reserveModalFeedback');
	const modalFooter = document.getElementById('reserveModalFooter');
	const confirmButton = document.getElementById('reserveConfirmButton');

	var msgstr = "";

	// Reseta o modal para o estado inicial
	modalBody.innerHTML = `<p>${msgstr["reserve_confirm_query"] || "Confirmar reserva?"}</p> 
                           <p><strong>MFN:</strong> ${mfn}</p>`; // Simples, por enquanto
	modalBody.style.display = 'block';
	modalLoading.style.display = 'none';
	modalFeedback.style.display = 'none';
	modalFooter.style.display = 'block';

	// Armazena os dados no botão de confirmação
	confirmButton.dataset.base = base;
	confirmButton.dataset.mfn = mfn;

	// Abre o modal
	modal.show();
}

// 2. Função para EXECUTAR a reserva (chamada pelo botão "Confirmar" do modal)
async function executarReserva(button) {
	const base = button.dataset.base;
	const mfn = button.dataset.mfn;

	const modalBody = document.getElementById('reserveModalBodyContent');
	const modalLoading = document.getElementById('reserveModalLoading');
	const modalFeedback = document.getElementById('reserveModalFeedback');
	const modalFooter = document.getElementById('reserveModalFooter');

	// Esconde o corpo e o rodapé, mostra o spinner
	modalBody.style.display = 'none';
	modalFooter.style.display = 'none';
	modalLoading.style.display = 'block';
	modalFeedback.style.display = 'none';

	// Prepara os dados para o 'reserve_action.php'
	const formData = new FormData();
	formData.append('base', base);
	formData.append('mfn', mfn);
	formData.append('lang', OpacLang); // Assume que 'OpacLang' está definida globalmente

	try {
		// Chama o "cérebro" PHP via AJAX (fetch)
		const response = await fetch('myabcd/reserve_action.php', {
			method: 'POST',
			body: formData
		});

		if (!response.ok) {
			throw new Error(`Erro HTTP: ${response.status}`);
		}

		const data = await response.json(); // Espera uma resposta JSON

		// Esconde o spinner
		modalLoading.style.display = 'none';

		// Mostra o feedback
		let feedbackClass = 'alert alert-danger';
		if (data.status === 'success') {
			feedbackClass = 'alert alert-success';
		}

		modalFeedback.innerHTML = `<div class="${feedbackClass}">${data.message}</div>`;
		modalFeedback.style.display = 'block';

		// Opcional: Modifica o rodapé para apenas "Fechar"
		// (Pode adicionar um novo rodapé se quiser)

	} catch (error) {
		// Erro de rede ou JSON
		console.error('Erro ao executar reserva:', error);
		modalLoading.style.display = 'none';
		modalFeedback.innerHTML = `<div class="alert alert-danger">Erro de comunicação com o servidor.</div>`;
		modalFeedback.style.display = 'block';
	}
}
/* =============================================== */
/* FIM - LÓGICA DO MODAL DE RESERVA (AJAX)         */
/* =============================================== */



  	function ShowHide(myDiv) {
  		if (myDiv=="myMail"){
  			document.getElementById("myReserve").style.display="none"
  		}else{
  			document.getElementById("myMail").style.display="none"
  			if (Total_No>=contador){
  				//alert("<?php echo $msgstr["front_nothing_to_reserve"]?>")
  				return
  			}
  		}
  		var x = document.getElementById(myDiv);
  		if (x.style.display === "none") {
    		x.style.display = "block";
  		} else {
    		x.style.display = "none";
  		}
	}

/* Clears cookies and returns to the home page */
function deleteAllCookies() {
	var cookies = document.cookie.split(";");

	for (var i = 0; i < cookies.length; i++) {
		var cookie = cookies[i];
		var eqPos = cookie.indexOf("=");
		var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
		document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
	}
}

/**
 * RefinF - VERSÃO CORRIGIDA PARA TRUNCAGEM ($)
 * Constrói uma nova URL com a expressão de busca refinada.
 */
function RefinF(Termino, ExpresionIgnorada, base) {
	// 1. IGNORAMOS o parâmetro 'ExpresionIgnorada' vindo do PHP, pois ele pode ter perdido o $.
	// Em vez disso, pegamos o valor do input hidden que garantimos estar correto (com $).
	var ExpresionCorreta = "";
	var inputExp = document.getElementById('Expresion');

	if (inputExp && inputExp.value) {
		ExpresionCorreta = inputExp.value;
	} else {
		// Fallback se o input não existir (improvável)
		ExpresionCorreta = ExpresionIgnorada;
	}

	// 2. Constrói a nova expressão booleana.
	var novaExpressao = ExpresionCorreta + " and (" + Termino + ")";

	// 3. Monta a nova URL
	var url = new URL(window.location.origin + window.location.pathname);
	url.searchParams.set('page', 'startsearch');
	url.searchParams.set('base', base);
	url.searchParams.set('Expresion', novaExpressao); // Usa a expressão com $ preservado
	url.searchParams.set('Opcion', 'directa');
	url.searchParams.set('desde', '1');
	url.searchParams.set('pagina', '1');

	// Mantém o idioma
	var lang = new URLSearchParams(window.location.search).get('lang');
	if (lang) {
		url.searchParams.set('lang', lang);
	}

	// 4. Redireciona
	window.location.href = url.toString();
}


function processarTermosLivres() {
	const form = document.getElementById('facetasForm');
	if (!form) return;

	const termosParaRefinar = form.termosLivres.value.trim();
	if (termosParaRefinar === '') return;

	// Pega a expressão original do input hidden (que agora tem o $ graças ao PHP)
	const expresionOriginal = form.Expresion.value;

	// Adiciona o novo termo. Se ele tiver $, será enviado na URL corretamente.
	const expresionDeRefinamento = `("TW_${termosParaRefinar}")`;
	const nuevaExpresion = `(${expresionOriginal}) and ${expresionDeRefinamento}`;

	const params = new URLSearchParams(window.location.search);
	params.set('Expresion', nuevaExpresion);
	// Removemos termosLivres da URL para limpar visualmente, ou mantemos se necessário
	// params.set('termosLivres', termosParaRefinar); 
	params.set('Opcion', 'directa');
	params.set('desde', '1');
	params.set('pagina', '1');
	params.delete('Sub_Expresion');

	window.location.href = window.location.pathname + '?' + params.toString();
}


function processarTermosLivres() {
	// Pega os elementos do formulário
	const form = document.getElementById('facetasForm');
	if (!form) {
		console.error("Formulário 'facetasForm' não encontrado.");
		return;
	}

	const termosParaRefinar = form.termosLivres.value.trim();
	if (termosParaRefinar === '') {
		return; // Não faz nada se o campo estiver vazio
	}

	// Pega a expressão de busca original do campo oculto
	const expresionOriginal = form.Expresion.value;

	// --- CORREÇÃO DEFINITIVA ---
	// Adiciona o novo termo COM as aspas duplas que o WXIS espera,
	// exatamente como na URL da DEMO.
	const expresionDeRefinamento = `("TW_${termosParaRefinar}")`;

	// Combina a expressão original com a nova.
	const nuevaExpresion = `(${expresionOriginal}) and ${expresionDeRefinamento}`;

	// Monta a nova URL
	const params = new URLSearchParams(window.location.search);

	params.set('Expresion', nuevaExpresion);
	params.set('termosLivres', termosParaRefinar);
	params.set('Opcion', 'directa');
	params.set('desde', '1');
	params.set('pagina', '1');
	params.delete('Sub_Expresion'); // Limpa a busca livre anterior

	window.location.href = window.location.pathname + '?' + params.toString();
}

function removerTermo(termoRemover) {
	let campoExp = document.getElementById('Expresion');
	if (!campoExp) return;

	let expresion = campoExp.value;

	// Normaliza espaços
	expresion = expresion.replace(/\s+/g, ' ').trim();

	// Escapa caracteres especiais do termo para o Regex, INCLUINDO O $
	// Isso garante que se o termo for "econom$", o regex busque "econom\$"
	const termoEscapado = termoRemover.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

	// Remove o termo (com ou sem parênteses e AND antes/depois)
	const termoRegex = new RegExp(`(\\s+and\\s+)?\\(?${termoEscapado}\\)?(\\s+and\\s+)?`, 'i');

	expresion = expresion.replace(termoRegex, (match, andBefore, andAfter) => {
		if (andBefore && andAfter) return ' and ';
		return '';
	});

	// Limpa ANDs soltos nas bordas
	expresion = expresion.replace(/^\s*and\s*|\s*and\s*$/gi, '').replace(/\s+and\s+/gi, ' and ').trim();

	// Reaplica parênteses apenas onde necessário
	if (expresion) {
		let termos = expresion
			.split(/\s+and\s+/i)
			.map(t => t.trim())
			.filter(t => t !== '')
			.map(t => {
				let termo = t;
				// Remove parênteses externos existentes para não duplicar
				while (termo.startsWith('(') && termo.endsWith(')')) {
					termo = termo.substring(1, termo.length - 1);
				}
				return `(${termo})`;
			});

		expresion = termos.join(' AND ');
	}

	// Atualiza o campo no DOM
	campoExp.value = expresion;

	const urlParams = new URLSearchParams(window.location.search);
	let finalUrl = new URL(window.location.origin + window.location.pathname);

	// 1. Garante o parâmetro 'base' (lê do input hidden do form ou da URL atual)
	let baseInput = document.getElementById('base');
	let currentBase = baseInput ? baseInput.value : urlParams.get('base');
	if (currentBase) {
		finalUrl.searchParams.set('base', currentBase);
	}

	// 2. Garante o parâmetro 'ctx' (se usar multi-contexto)
	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		finalUrl.searchParams.set('ctx', OpacContext);
	} else if (urlParams.has('ctx')) {
		finalUrl.searchParams.set('ctx', urlParams.get('ctx'));
	}

	// 3. Garante o idioma atual
	if (typeof OpacLang !== 'undefined' && OpacLang !== "") {
		finalUrl.searchParams.set('lang', OpacLang);
	} else if (urlParams.has('lang')) {
		finalUrl.searchParams.set('lang', urlParams.get('lang'));
	}

	// 4. Decide a rota final
	if (expresion === "") {
		// Expressão vazia: Volta limpo para a Home preservando estritamente a Base
		window.location.href = finalUrl.toString();
	} else {
		// Continua a pesquisa com a expressão refinada
		finalUrl.searchParams.set('Expresion', expresion);
		finalUrl.searchParams.set('page', 'startsearch');
		finalUrl.searchParams.set('Opcion', 'directa');
		finalUrl.searchParams.set('desde', '1');
		finalUrl.searchParams.set('pagina', '1');
		window.location.href = finalUrl.toString();
	}
}


function clearAndRedirect(link) {
	deleteAllCookies();

	// Tenta obter o parâmetro lang da URL atual
	const urlParams = new URLSearchParams(window.location.search);
	const lang = urlParams.get("lang");

	const hasQuery = link.includes("?");
	const separator = hasQuery ? "&" : "?";

	// Redireciona com o parâmetro lang atual (se houver)
	const finalLink = lang ? link + separator + "lang=" + encodeURIComponent(lang) : link;
	document.location = finalLink;
}


// MODAL PERMALINK

document.addEventListener('DOMContentLoaded', function () {
	var modalElement = document.getElementById('registroModal');
	if (modalElement) {
		var modal = new bootstrap.Modal(modalElement);
		modal.show();
	}
});


	function copiarLink() {
        const url = window.location.href;
	const btn = document.getElementById('btnCopiar');
	const originalHTML = btn.innerHTML;

        navigator.clipboard.writeText(url).then(() => {
		// Feedback visual com ícone de check
			btn.innerHTML = '<i class="fas fa-clipboard"></i> Copiado!';
			btn.classList.remove('btn-outline-primary');
			btn.classList.add('btn-success');

            setTimeout(() => {
				btn.innerHTML = '<i class="far fa-clipboard"></i> Copiar link direto';
				btn.classList.remove('btn-success');
				btn.classList.add('btn-outline-primary');
            }, 2000);
        }).catch(err => {
		alert("Erro ao copiar: " + err);
        });
    }

// Variável global para guardar qual elemento estava em foco antes do modal abrir
let lastFocusedElement = null;

/**
 * Exibe a janela modal com o link permanente para o registro.
 * @param {HTMLElement} button - O botão que foi clicado.
 */
function showPermalinkModal(button) {
	// Guardamos o botão que abriu o modal para devolver o foco a ele depois
	lastFocusedElement = button;

	const modalElement = document.getElementById('permalinkModal');
	if (!modalElement) {
		console.error('Elemento do modal #permalinkModal não encontrado!');
		return;
	}
	const permalinkModal = bootstrap.Modal.getOrCreateInstance(modalElement);

	const base = button.getAttribute('data-base');
	const k = button.getAttribute('data-k');

	const currentPath = window.location.pathname;
	const directoryPath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
	const baseUrl = window.location.origin + directoryPath;
	const fullUrl = baseUrl + "?base=" + encodeURIComponent(base) + "&k=" + encodeURIComponent(k);

	const input = document.getElementById('permalinkInput');
	input.value = fullUrl;

	const copyButton = document.getElementById('copyPermalinkButton');
	copyButton.textContent = 'Copiar';
	copyButton.classList.remove('btn-success');
	copyButton.classList.add('btn-primary');

	permalinkModal.show();
}

/**
 * Copia o conteúdo do campo de texto do permalink para a área de transferência.
 */
function copyPermalink() {
	const input = document.getElementById('permalinkInput');

	navigator.clipboard.writeText(input.value).then(() => {
		const copyButton = document.getElementById('copyPermalinkButton');
		copyButton.textContent = 'Copiado!';
		copyButton.classList.remove('btn-primary');
		copyButton.classList.add('btn-success');
	}).catch(err => {
		console.error('Erro ao copiar o link: ', err);
		alert('Não foi possível copiar o link.');
	});
}

// --- CORREÇÃO DE FOCO ---
const permalinkModalElement = document.getElementById('permalinkModal');
if (permalinkModalElement) {
	// MUDANÇA: Usando o evento 'hide.bs.modal' que dispara ANTES do modal fechar
	permalinkModalElement.addEventListener('hide.bs.modal', function () {
		if (lastFocusedElement) {
			// Devolve o foco ao botão original imediatamente
			lastFocusedElement.focus();
		}
	});
}





// ===============================================
// INÍCIO - LÓGICA DO MODAL DE DETALHES
// ===============================================

document.addEventListener('DOMContentLoaded', function () {
	const recordDetailModalElement = document.getElementById('recordDetailModal');

	// Certifica-se de que o modal existe na página antes de adicionar listeners
	if (!recordDetailModalElement) {
		console.warn("Elemento do modal #recordDetailModal não encontrado.");
		return;
	}

	const modal = new bootstrap.Modal(recordDetailModalElement);
	const modalContent = document.getElementById('modalRecordContent');
	const modalFormatSelectorContainer = document.getElementById('modalFormatSelectorContainer');
	const modalActionButtons = document.getElementById('modalActionButtons');
	const modalLoadingIndicator = document.getElementById('modalLoadingIndicator');



	// --- Listener para os BOTÕES de formato (DELEGAÇÃO DE EVENTOS) ---
	// Este listener é adicionado APENAS UMA VEZ no DOMContentLoaded
	if (modalFormatSelectorContainer) {
		modalFormatSelectorContainer.addEventListener('click', function (event) {
			const targetButton = event.target.closest('.format-button'); // Verifica se o clique foi em um botão de formato
			if (targetButton && !targetButton.classList.contains('active')) { // Só faz algo se clicar em um botão INATIVO
				const selectedFormat = targetButton.dataset.format;
				const currentBase = modalFormatSelectorContainer.dataset.currentBase; // Pega do container
				const currentMfn = modalFormatSelectorContainer.dataset.currentMfn;   // Pega do container

				if (currentBase && currentMfn && selectedFormat) {
					// Chama a função para buscar o novo formato
					fetchRecordDetails(currentBase, currentMfn, selectedFormat);

					// Opcional: Atualizar visualmente o estado 'active' imediatamente para feedback rápido
					// Embora fetchRecordDetails vá fazer isso ao recarregar, pode demorar um pouco.
					// Descomente se quiser feedback visual instantâneo:
					// const allButtons = modalFormatSelectorContainer.querySelectorAll('.format-button');
					// allButtons.forEach(btn => btn.classList.remove('active'));
					// targetButton.classList.add('active');
				}
			}
		});
	}


	// --- Função ÚNICA para buscar e exibir detalhes (e formatos) ---
	async function fetchRecordDetails(base, mfn, formato = null) {
		// Mostra o indicador de carregamento e esconde o conteúdo anterior
		if (modalLoadingIndicator) modalLoadingIndicator.style.display = 'block';
		if (modalContent) modalContent.style.display = 'none';
		if (modalActionButtons) modalActionButtons.innerHTML = '';
		if (modalFormatSelectorContainer) modalFormatSelectorContainer.innerHTML = ''; // Limpa botões antigos SEMPRE

		let url = `get_record_details.php?base=${encodeURIComponent(base)}&mfn=${encodeURIComponent(mfn)}&lang=${encodeURIComponent(OpacLang)}`;

		if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
			url += `&ctx=${encodeURIComponent(OpacContext)}`;
		}
		
		if (formato) {
			url += `&Formato=${encodeURIComponent(formato)}`;
		}

		try {
			const response = await fetch(url);
			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}
			const data = await response.json();

			if (modalLoadingIndicator) modalLoadingIndicator.style.display = 'none';

			if (data.error) {
				if (modalContent) modalContent.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
				if (modalContent) modalContent.style.display = 'block';
				// Mesmo com erro na busca de conteúdo, tentamos mostrar os botões se vieram
				// return; // Não paramos aqui ainda
			} else {
				// Preenche o conteúdo do registro SOMENTE se não houve erro
				if (modalContent) {
					modalContent.innerHTML = data.recordHtml || '<p>Nenhum conteúdo retornado.</p>';
					modalContent.style.display = 'block';
				}
			}

			// Preenche os botões de ação (sempre tenta preencher)
			if (modalActionButtons) {
				modalActionButtons.innerHTML = data.actionButtonsHtml || '';
			}

			// --- RECRIA os botões de formato a cada chamada ---
			if (modalFormatSelectorContainer && data.availableFormats && data.availableFormats.length > 0) {

				// Determina qual formato está ativo (o solicitado ou o padrão)
				let activeFormat = formato;
				if (!activeFormat && data.availableFormats.length > 0) {
					const defaultFormatFromBackend = data.availableFormats.find(fmt => fmt.is_default === true);
					if (defaultFormatFromBackend) {
						activeFormat = defaultFormatFromBackend.name;
					} else {
						activeFormat = data.availableFormats[0].name;
					}
				}
				if (!activeFormat && data.availableFormats.length > 0) activeFormat = data.availableFormats[0].name; // Garantia

				// Gera o HTML do grupo de botões
				let buttonsHTML = `<div class="btn-group btn-group-sm format-button-group" role="group" aria-label="${Msgstr['front_formato_exibicao'] || 'Formatos de exibição'}">`;
				data.availableFormats.forEach(fmt => {
					const isActive = (fmt.name === activeFormat);
					buttonsHTML += `<button type="button" 
                                           class="btn btn-outline-primary format-button ${isActive ? 'active' : ''}" 
                                           data-format="${fmt.name}">
                                        ${fmt.label}
                                    </button>`;
				});
				buttonsHTML += '</div>';
				modalFormatSelectorContainer.innerHTML = buttonsHTML; // Insere os botões recriados

				// Armazena base e mfn no container (IMPORTANTE para o listener)
				modalFormatSelectorContainer.dataset.currentBase = base;
				modalFormatSelectorContainer.dataset.currentMfn = mfn;

			} else if (modalFormatSelectorContainer) {
				modalFormatSelectorContainer.innerHTML = ''; // Limpa se não houver formatos
				console.warn("Nenhum formato disponível retornado pelo backend.");
			}


		} catch (error) {
			console.error('Erro ao buscar detalhes do registro:', error);
			if (modalLoadingIndicator) modalLoadingIndicator.style.display = 'none';
			if (modalContent) modalContent.innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes. Tente novamente.</div>`;
			if (modalContent) modalContent.style.display = 'block';
			// Limpa os botões em caso de erro grave de fetch
			if (modalFormatSelectorContainer) modalFormatSelectorContainer.innerHTML = '';
			if (modalActionButtons) modalActionButtons.innerHTML = '';
		}
	} // Fim de fetchRecordDetails


	// --- Listener para os BOTÕES de formato (DELEGAÇÃO DE EVENTOS) ---
	// Este listener AGORA chama fetchRecordDetails
	if (modalFormatSelectorContainer) {
		modalFormatSelectorContainer.addEventListener('click', function (event) {
			const targetButton = event.target.closest('.format-button');
			if (targetButton && !targetButton.classList.contains('active')) { // Só age se clicar em botão inativo
				const selectedFormat = targetButton.dataset.format;
				const currentBase = modalFormatSelectorContainer.dataset.currentBase;
				const currentMfn = modalFormatSelectorContainer.dataset.currentMfn;

				if (currentBase && currentMfn && selectedFormat) {
					// CHAMA A FUNÇÃO PRINCIPAL novamente, passando o novo formato
					fetchRecordDetails(currentBase, currentMfn, selectedFormat);

					// REMOVIDA a atualização visual imediata aqui, pois fetchRecordDetails recriará os botões
				}
			}
		});
	}

	// --- Listener para os botões "Ver Detalhes" --- (Mantém como estava)
	document.body.addEventListener('click', function (event) {
		const targetButton = event.target.closest('.open-detail-modal');
		if (targetButton) {
			const base = targetButton.dataset.base;
			const mfn = targetButton.dataset.mfn;
			if (base && mfn) {
				fetchRecordDetails(base, mfn); // Chama a função principal
			} else { /* ... erro ... */ }
		}
	});

	// --- Listener para fechar modal --- (Mantém como estava)
	if (recordDetailModalElement) {
		recordDetailModalElement.addEventListener('hidden.bs.modal', function () {
			if (modalContent) modalContent.innerHTML = '';
			if (modalFormatSelectorContainer) modalFormatSelectorContainer.innerHTML = ''; // Limpa botões ao fechar
			if (modalActionButtons) modalActionButtons.innerHTML = '';
		});
	}

	// }); // Fechamento do DOMContentLoaded (SE o código estiver dentro dele)

	// --- Listener para os botões "Ver Detalhes" ---
	// Usa delegação de eventos para funcionar com resultados carregados dinamicamente (se aplicável no futuro)
	document.body.addEventListener('click', function (event) {
		// Verifica se o elemento clicado (ou um de seus pais) é o botão
		const targetButton = event.target.closest('.open-detail-modal');

		if (targetButton) {
			const base = targetButton.dataset.base;
			const mfn = targetButton.dataset.mfn;

			if (base && mfn) {
				// Chama a função para buscar os dados e preencher o modal
				fetchRecordDetails(base, mfn);
				// O modal é aberto automaticamente pelos atributos data-bs-toggle/target no botão
			} else {
				console.error("Botão 'Ver Detalhes' não possui data-base ou data-mfn.");
			}
		}
	});

	// Opcional: Limpar o conteúdo do modal quando ele for fechado
	recordDetailModalElement.addEventListener('hidden.bs.modal', function () {
		if (modalContent) modalContent.innerHTML = '';
		if (modalFormatSelectorContainer) modalFormatSelectorContainer.innerHTML = '';
		if (modalActionButtons) modalActionButtons.innerHTML = '';
	});

});


/**
 * PASSO 2: Submete a reserva após a confirmação do usuário.
 * v2.0 - Passa CN e Título para o gravador.
 */
function SubmitReservation(base, mfn, cn, title) { // <-- Recebe CN e Título
	var modal = document.getElementById('abcdModal');
	var modalLoading = modal.querySelector('.modal-loading-spinner');
	var modalFeedback = modal.querySelector('.modal-feedback-area');
	var modalFooter = modal.querySelector('.modal-footer');

	var diasEsperaInput = document.getElementById('dias_espera_input');
	var dias_espera = (diasEsperaInput) ? diasEsperaInput.value : '';

	modalLoading.style.display = 'block';
	modalFeedback.style.display = 'none';
	modalFooter.style.display = 'none';

	// 2. Prepara a chamada AJAX (para o gravador)
	var xhr = new XMLHttpRequest();
	var formData = new FormData();
	formData.append('base', base);
	formData.append('mfn', mfn); // (O gravador não usa o MFN, mas enviamos)
	formData.append('cn', cn); // <-- ENVIA O CN
	formData.append('title', title); // <-- ENVIA O TÍTULO
	formData.append('dias_espera', dias_espera);

	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		formData.append('ctx', OpacContext);
	}

	var ajaxUrl = OpacHttpPath + 'myabcd/reserve_action.php';


	xhr.open('POST', ajaxUrl, true);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

	// 3. Handler da resposta final (sem alteração)
	xhr.onload = function () {
		modalLoading.style.display = 'none';
		var feedbackClass = 'alert alert-danger';
		var message = OpacMsgstr.ajaxError || 'Erro de comunicação.';

		if (xhr.status >= 200 && xhr.status < 400) {
			try {
				var data = JSON.parse(xhr.responseText);
				if (data.status === 'success') {
					feedbackClass = 'alert alert-success';
				}
				message = data.message;
			} catch (e) {
				message = OpacMsgstr.jsonError || 'O servidor enviou uma resposta inválida.';
				console.error("Resposta não-JSON recebida:", xhr.responseText);
			}
		}

		modalFeedback.innerHTML = '<div class="' + feedbackClass + '">' + message + '</div>';
		modalFeedback.style.display = 'block';

		modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + (OpacMsgstr.closeBtn || "Fechar") + '</button>';
		modalFooter.style.display = 'block';
	};

	// 4. Handler de erro de rede
	xhr.onerror = function () {
		modalLoading.style.display = 'none';
		modalFeedback.innerHTML = '<div class="alert alert-danger">' + (OpacMsgstr.ajaxError || 'Erro de comunicação.') + '</div>';
		modalFeedback.style.display = 'block';
		modalFooter.style.display = 'block';
	};

	// 5. Envia
	xhr.send(formData);
}


/**
 * PASSO 1: Prepara a reserva, verifica permissões e busca dados do item.
 * v2.0 - Passa CN e Título para o SubmitReservation
 */
function ExecutarReserva(base, mfn) {
	if (typeof OpacHttpPath === 'undefined' || OpacHttpPath === "") {
		alert('Erro de configuração: OpacHttpPath não está definido. Verifique head.php e head-my.php.');
		return;
	}

	var modal = document.getElementById('abcdModal');
	if (!modal) {
		alert("Erro de front-end: Modal (#abcdModal) não encontrado.");
		return;
	}
	var modalTitle = modal.querySelector('.modal-title');
	var modalLoading = modal.querySelector('.modal-loading-spinner');
	var modalFeedback = modal.querySelector('.modal-feedback-area');
	var modalFooter = modal.querySelector('.modal-footer');

	// 1. Prepara o modal
	modalTitle.innerHTML = OpacMsgstr.reserveTitle || "Reservar Item";
	modalLoading.style.display = 'block';
	modalFeedback.style.display = 'none';
	modalFooter.style.display = 'none';

	var bsModal = new bootstrap.Modal(modal);
	bsModal.show();

	// 2. Prepara a chamada AJAX (para o preparador)
	var xhr = new XMLHttpRequest();

	var ajaxUrl = OpacHttpPath + 'myabcd/prepare_reservation.php?base=' + encodeURIComponent(base) + '&mfn=' + encodeURIComponent(mfn);

	// Se a variável global OpacContext estiver definida, anexa na URL
	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		ajaxUrl += '&ctx=' + encodeURIComponent(OpacContext);
	}

	xhr.open('GET', ajaxUrl, true);
	console.log("Usando OpacHttpPath para preparar reserva:", OpacHttpPath);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

	// 3. Handler da resposta
	xhr.onload = function () {
		modalLoading.style.display = 'none';
		var feedbackClass = 'alert alert-danger';
		var message = OpacMsgstr.ajaxError || 'Erro de comunicação.';
		var footerHtml = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + (OpacMsgstr.closeBtn || "Fechar") + '</button>';

		if (xhr.status >= 200 && xhr.status < 400) {
			try {
				var data = JSON.parse(xhr.responseText);

				if (data.status === 'confirmation_required') {
					// SUCESSO! Mostra o formulário de confirmação.
					feedbackClass = 'alert alert-info';

					var formHtml = '<p>' + (OpacMsgstr.reserve_confirm_query || 'Confirmar reserva para o item:') + '</p>'
						+ '<strong>' + data.title + '</strong>'
						+ '<hr class="my-3">'
						+ '<div class="mb-3">'
						+ '  <label for="dias_espera_input" class="form-label">' + (OpacMsgstr.reserve_wait_days || 'Estou disposto a esperar por (dias):') + '</label>'
						+ '  <input type="number" class="form-control" id="dias_espera_input" value="30" min="1">'
						+ '  <div class="form-text">' + (OpacMsgstr.reserve_wait_days_desc || 'Padrão: 30 dias.') + '</div>'
						+ '</div>';
					message = formHtml;

					// CORREÇÃO: Passa os dados (CN e Título) para o SubmitReservation
					var cn_escaped = data.cn.replace(/'/g, "\\'");
					var title_escaped = data.title.replace(/'/g, "\\'");

					footerHtml = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + (OpacMsgstr.front_cancelar || "Cancelar") + '</button>'
						+ '<button type="button" class="btn btn-primary" onclick="SubmitReservation(\'' + data.base + '\', \'' + data.mfn + '\', \'' + cn_escaped + '\', \'' + title_escaped + '\')">'
						+ (OpacMsgstr.reserve_confirm_button || "Confirmar") + '</button>';

				} else if (data.status === 'auth_required') {
					feedbackClass = 'alert alert-warning';
					message = data.message;
				} else {
					message = data.message;
				}
			} catch (e) {
				message = OpacMsgstr.jsonError || 'O servidor enviou uma resposta inválida.';
				console.error("Resposta não-JSON recebida:", xhr.responseText);
			}
		}

		modalFeedback.innerHTML = '<div class="' + feedbackClass + '">' + message + '</div>';
		modalFeedback.style.display = 'block';

		modalFooter.innerHTML = footerHtml;
		modalFooter.style.display = 'block';
	};

	// ... (handler de erro de rede) ...
	xhr.onerror = function () {
		modalLoading.style.display = 'none';
		modalFeedback.innerHTML = '<div class="alert alert-danger">' + (OpacMsgstr.ajaxError || 'Erro de comunicação.') + '</div>';
		modalFeedback.style.display = 'block';
		modalFooter.style.display = 'block';
	};

	// 5. Envia
	xhr.send();
}




/**
 * Executa o cancelamento de uma reserva via AJAX (VERSÃO MULTILÍNGUE)
 * v1.5 - Esta função estava correta.
 */
function CancelReservation(waitId) {
	if (typeof OpacHttpPath === 'undefined' || OpacHttpPath === "") {
		alert('Erro de configuração: OpacHttpPath não está definido em head.php.');
		return;
	}

	var modal = document.getElementById('abcdModal');
	if (!modal) {
		alert("Erro de front-end: Modal (#abcdModal) não encontrado.");
		return;
	}
	var modalTitle = modal.querySelector('.modal-title');
	var modalBody = modal.querySelector('.modal-body');
	var modalLoading = modal.querySelector('.modal-loading-spinner');
	var modalFeedback = modal.querySelector('.modal-feedback-area');
	var modalFooter = modal.querySelector('.modal-footer');

	// 1. Prepara o modal com texto do PHP
	modalTitle.innerHTML = OpacMsgstr.cancelTitle || "Cancelar Reserva";
	modalBody.style.display = 'block';
	modalLoading.style.display = 'block';
	modalFeedback.style.display = 'none';
	modalFooter.style.display = 'none';

	var bsModal = new bootstrap.Modal(modal);
	bsModal.show();

	// 2. Prepara a chamada AJAX
	var xhr = new XMLHttpRequest();
	var formData = new FormData();
	formData.append('waitid', waitId);

	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
		formData.append('ctx', OpacContext);
	}

	console.log("Usando OpacHttpPath para cancelar reserva:", OpacHttpPath);

	var ajaxUrl = 'cancelreservation.php';
	xhr.open('POST', ajaxUrl, true);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

	// 3. Handler da resposta (Este código está correto)
	xhr.onload = function () {
		modalLoading.style.display = 'none';
		var feedbackClass = 'alert alert-danger';
		var message = OpacMsgstr.ajaxError || 'Erro de comunicação.';
		var showFooter = true;

		if (xhr.status >= 200 && xhr.status < 400) {
			try {
				var data = JSON.parse(xhr.responseText);

				if (data.status === 'auth_required') {
					feedbackClass = 'alert alert-warning';
					message = data.message + ' <br>(' + (OpacMsgstr.reloadingLogin || 'Abrindo o login...') + ')';
					showFooter = false;

					var loginModalEl = document.getElementById('loginModal');
					if (!loginModalEl) {
						console.error("Erro fatal: O modal #loginModal não foi encontrado na página.");
						message = "Erro de front-end: #loginModal não encontrado.";
						showFooter = true;
					} else {
						setTimeout(function () {
							bsModal.hide();
							var bsLoginModal = new bootstrap.Modal(loginModalEl);
							bsLoginModal.show();
						}, 1500);
					}
				} else if (data.status === 'success') {
					feedbackClass = 'alert alert-success';
					message = data.message + ' <br>(' + (OpacMsgstr.reloading || 'Atualizando a página...') + ')';
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					message = data.message;
				}
			} catch (e) {
				message = OpacMsgstr.jsonError || 'O servidor enviou uma resposta inválida.';
				console.error("Resposta não-JSON recebida:", xhr.responseText);
			}
		}

		modalFeedback.innerHTML = '<div class="' + feedbackClass + '">' + message + '</div>';
		modalFeedback.style.display = 'block';

		if (showFooter) {
			modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + (OpacMsgstr.closeBtn || "Fechar") + '</button>';
			modalFooter.style.display = 'block';
		}
	};

	// 4. Handler de erro de rede
	xhr.onerror = function () {
		modalLoading.style.display = 'none';
		modalFeedback.innerHTML = '<div class="alert alert-danger">' + (OpacMsgstr.ajaxError || 'Erro de comunicação.') + '</div>';
		modalFeedback.style.display = 'block';
		modalFooter.style.display = 'block';
	};

	// 5. Envia
	xhr.send(formData);
}

/**
 * Executa a renovação de um empréstimo via AJAX.
 * Chamado pelo botão 'Renovar' em myabcd/inc/loans.php
 * @param {string} copyId O ID do exemplar (usado para encontrar os campos hidden)
 */
function ExecutarRenovacao(copyId) {

	// 1. Coletar dados dos campos hidden (como a função antiga fazia)
	try {
		var copyType = document.getElementById('copytypeh' + copyId).value;
		var loanId = document.getElementById('loanidh' + copyId).value;
	} catch (e) {
		alert("Erro de front-end: Não foi possível ler os dados do empréstimo (campos hidden).");
		return;
	}

	var modal = document.getElementById('abcdModal');
	if (!modal) {
		alert("Erro de front-end: Modal (#abcdModal) não encontrado.");
		return;
	}
	var modalTitle = modal.querySelector('.modal-title');
	var modalLoading = modal.querySelector('.modal-loading-spinner');
	var modalFeedback = modal.querySelector('.modal-feedback-area');
	var modalFooter = modal.querySelector('.modal-footer');

	// 2. Prepara o modal
	modalTitle.innerHTML = OpacMsgstr.renewTitle || "Renovar Empréstimo";
	modalLoading.style.display = 'block';
	modalFeedback.style.display = 'none';
	modalFooter.style.display = 'none';

	var bsModal = new bootstrap.Modal(modal);
	bsModal.show();

	// 3. Prepara a chamada AJAX
	var xhr = new XMLHttpRequest();
	var formData = new FormData();
	formData.append('copytype', copyType); // O PHP precisa disto
	formData.append('loanid', loanId);     // O PHP precisa disto

	if (typeof OpacContext !== 'undefined' && OpacContext !== "") {
			formData.append('ctx', OpacContext);
		}

	// Caminho relativo, pois estamos em /myabcd/
	var ajaxUrl = 'loanrenovation.php';
	xhr.open('POST', ajaxUrl, true);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

	// 4. Handler da resposta
	xhr.onload = function () {
		modalLoading.style.display = 'none';
		var feedbackClass = 'alert alert-danger';
		var message = OpacMsgstr.ajaxError || 'Erro de comunicação.';

		if (xhr.status >= 200 && xhr.status < 400) {
			try {
				var data = JSON.parse(xhr.responseText);
				if (data.status === 'success') {
					feedbackClass = 'alert alert-success';
					// Recarrega a página para mostrar a nova data de devolução
					message = data.message + ' <br>(' + (OpacMsgstr.reloading || 'Atualizando a página...') + ')';
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					message = data.message; // Ex: "Limite de renovações atingido."
				}
			} catch (e) {
				message = OpacMsgstr.jsonError || 'O servidor enviou uma resposta inválida.';
				console.error("Resposta não-JSON recebida:", xhr.responseText);
			}
		}

		modalFeedback.innerHTML = '<div class="' + feedbackClass + '">' + message + '</div>';
		modalFeedback.style.display = 'block';

		modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + (OpacMsgstr.closeBtn || "Fechar") + '</button>';
		modalFooter.style.display = 'block';
	};

	// 5. Handler de erro de rede
	xhr.onerror = function () {
		modalLoading.style.display = 'none';
		modalFeedback.innerHTML = '<div class="alert alert-danger">' + (OpacMsgstr.ajaxError || 'Erro de comunicação.') + '</div>';
		modalFeedback.style.display = 'block';
		modalFooter.style.display = 'block';
	};

	// 6. Envia
	xhr.send(formData);
}