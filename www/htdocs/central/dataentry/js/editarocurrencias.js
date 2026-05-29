/*
20250402 fho4abcd More readable code, improve generated html, add translation and hovered texts
20250402 fho4abcd Copy default from worksheet to <input value=...>, replace dropdown by button
20250901 fho4abcd Copy default from worksheet for dropdown list in case of redraw command
20260529 rogercgui Implement type IND for indicators, added historical fallback for older databases that still use ‘S’ for indicators, added comments and improved code readability
*/
// The occurrences of the field / para colocar las ocurrencias del campo
var valoresCampo=new Array(200)
// The breakdown of the field's subfields / para colocar el desglose de los subcampos del campo
var SubCampos=new Array(23)
var lista_sc=Array()
// Selected tag (with prefix "tag"
var TagCampo=window.opener.document.forma1.TagActivo.value
// Field of the edit sheet(fmt.php) / Fdt del campo
document.forma1.tagcampo.value=window.opener.document.forma1.valor.value
// Number of field occurrences / no. de ocurrencias del campo
document.forma1.occur.value=window.opener.document.forma1.occur.value
// Index in process / indice en proceso
document.forma1.ep.value=window.opener.document.forma1.ep.value
// Index to input type=text in process / indice al input type=text en proceso
document.forma1.NoVar.value=window.opener.document.forma1.NoVar.value
// Field content / Contenido del campo
Contenido=window.opener.document.forma1.conte.value
// Breakdown of the field's subfields (values from the FMT file) / Desglose de los subcampos del campo (valores tomados del archivo FDT
SubC=window.opener.document.forma1.SubC.value
// Repeat value (R/
Repetible=window.opener.document.forma1.Repetible.value
// Format for extracting authority lists / Formato para extracción de las listas de autoridades
Formato=window.opener.document.forma1.Formato_ex.value

/*alert("Tag="+TagCampo+
	"\nField="+document.forma1.tagcampo.value+
	"\nOccurence="+document.forma1.occur.value+
	"\nIndex="+document.forma1.ep.value+
	"\nIndex to text in proces="+document.forma1.NoVar.value+
	"\nField content="+Contenido+
	"\nSubfields(SubC)="+SubC+
	"\nRepeat="+Repetible+
	"\nDisplay_as '$$$' Extract_as="+Formato)
*/
// The first line that contains the full description of the field is removed./ se elimina la primera línea que que ella contiene la descripción total del campo
var tag=""
var X_tag=""
X_tag="document.forma1."+TagCampo
document.forma1.base.value=window.opener.document.forma2.base.value
base=document.forma1.base.value
document.forma1.cipar.value=window.opener.document.forma2.cipar.value
cipar=document.forma1.cipar.value

ixpos=SubC.indexOf("\n")
SubC=SubC.substr(ixpos+1)
Valores=window.opener.document.forma1.conte.value
Occ=window.opener.document.forma1.occur.value
if (Occ==0) {Occ=1}
i=window.opener.document.forma1.ep.value

SubCampos = SubC.split("\n")
nSC=SubCampos.length

// The array of the field occurrences is obtained / se obtiene el arreglo de las ocurrencias del campo
valoresCampo = Contenido.split("\n")

// The Select is created for the occurrences of the field/  Se crea el Select para las ocurrencias del campo
Titulo = window.opener.NombreC
//  alert( "titel="+Titulo)

Tx = Titulo.split('|')
// Fields: 0:Type, 1:Tag, 2:Title, 3:I, 4:Repeatable, 5:Subfield(s), 6:Input type, 7:rows, 8:cols,
//         9:PL-Type, 10:PL-Name, 11:PL:Prefix, 12:PL-Detail, 13:List-as, 14:Extract-as,
//         15:Default, 16:Help, 17:Help-URL, 18:Link-FDT, 19:Req?, 20:Field-Validation


// ==== ABCD CORRECTION: DYNAMIC RECONSTRUCTION OF SUBFIELDS ====
// Ensures that the renderer reads all subfields (including IND)
// even if the cataloguer has forgotten to declare them in the main FDT.
var subc_reais = "";
for (var k = 0; k < nSC; k++) {
	if (Trim(SubCampos[k]) != "") {
		var xparts = SubCampos[k].split('|');
		if (xparts.length > 5 && xparts[5]) {
			subc_reais += xparts[5];
		}
	}
}
if (subc_reais != "") {
	Tx[5] = subc_reais;
}

// write the leading question mark
document.write("<table width=950 cellpadding=0 cellspacing=0>")
if (Trim(Tx[17])!=""){
	document.write("<td class=mmed0 colspan=2><a href='javascript:Ayuda(\"\",\""+Tx[17]+"\")'><i class=\"fas fa-question\"></i></a>")
}else{
	if (Tx[16]==1) document.write("<td class=mmed0 colspan=2><a href='javascript:Ayuda("+Tx[1]+",\"\")'><i class=\"fas fa-question\"></i></a>")
}
// write the tag title and tag number
document.write("<b> <font size=2>"+Tx[2]+" ("+Tx[1]+")</b></td> ")

document.write('<tr><td >')
// If the main field is repeatable write extra information
Tx_Rep=Tx[4]
if (Tx_Rep==1){
    if (Repetible=="R") {
	document.write("<a class='bt-fdt-blue' href=javascript:AgregarOcurrencia() title='"+trn_addoccur+"'><i class='fas fa-plus'></i>")
    }
    document.write("<a class='bt-fdt' href=javascript:EliminarOcurrencia() title='"+trn_deloccur+"'><i class='far fa-trash-alt'></i></a>")
    if (Repetible=="R") {
	document.write("<a class='bt-fdt' href=javascript:SubirOcurrencia('lista') title='"+trn_moveoccup+"'><i class='fas fa-caret-up'></i></a>")
	document.write("<a class='bt-fdt' href=javascript:BajarOcurrencia('lista') title='"+trn_moveoccdown+"'><i class='fas fa-caret-down'></i></a> &nbsp;")
    }
}
document.writeln("</td>")
// Write a selection box with main occurrences
Occ=valoresCampo.length
if (Occ>5){kOc=5} else {kOc=Occ}
document.write("<td><select name=lista style='width:800px;background-color=#F2F6F8;'  size="+kOc)
document.write(" id=occselect onChange=\"javascript:TerminoSeleccionado();\" title='"+trn_occlist+"'>")
for (j=0; j<=Occ-1; j++) {
	document.write("   <option  ")
	if (j==0) {
		document.write(" selected ")
	}
	valores=""
	if (Trim(valoresCampo[j])!="") {
		valores=valoresCampo[j].replace(/\"/g, "&quot;")
	}
       	document.write(" value=\""+valores+"\">"+valores)
}
document.writeln('</select></td></table><br>')

// Table to place the subfields of the occurrence / Tabla para colocar los subcampos de la ocurrencia

// This line is added to force the "tag" object to always be an array even if it is a single subfield.
// Esta linea se agrega para obligar que el objeto "tag" siempre sea un arreglo aún cuando sea un solo subcampo
document.writeln("<input type=hidden name=tag"+tag+" value=''>")

function returnObjById( id )
{
    //alert("returnObjById\nid="+id)
    if (document.getElementById)
        var returnVar = document.getElementById(id);
    else if (document.all)
        var returnVar = document.all[id];
    else if (document.layers)
        var returnVar = document.layers[id];
    return returnVar;
}

function Redraw(xsalida,newSc,add_name){
    // If there are no indicators but the field must have them, the two blank boxes open.
    // si no hay indicadores pero el campo los debe tener se abren las dos casillas en blanco
    // alert("Redraw\nxsalida="+xsalida+"\newSc="+newSc+"\nadd_name="+add_name)
    orgxsalida=Trim(xsalida) // the original xsalida is empty for new records
    inctr=""
    indicadores=""
    if (!is_marc=="S"){
        if (xsalida=='' || xsalida=='^-'){
            for (i=0;i<Tx[5].length;i++){
                sc=Tx[5].substr(i,1)
                xsalida+='^'+sc
            }
        }
    }else{
        if (Tx[5].substr(0,1)==1 && is_marc=="S"){
            ixpos=xsalida.indexOf('^')
            if (ixpos!=2){
                indicadores='  '
	    }else{
                indicadores=xsalida.substr(0,ixpos)
	    }
            xsalida=xsalida.substr(ixpos)
            xsalida='^I1'+indicadores.substr(0,1)+'^I2'+indicadores.substr(1,1)+xsalida
        }
    }
    campos=xsalida.split('^')
    vc=Array()
    icampos=-1
    // The contents of the fields are placed in an array / se coloca en un arreglo el contenido de los campos
    for (i=1;i<campos.length;i++){
        if (campos[i]!=""){
            if (i<3 && indicadores!=""){
                cc=campos[i].substr(0,2)
                len_c=2
            }else{
                cc=campos[i].substr(0,1)
                len_c=1
            }
            if (cc in vc){
                vc[cc]=vc[cc]+'$$$$$'+campos[i].substr(len_c)
            }else{
                vc[cc]=campos[i].substr(len_c)
            }
        }
    }
    M=-1
    strsubc=""
    html="<table border=0 cellspacing=1 class=\"listTable\">"
    inicio=""
    Desc_sc=Array()
    // The name of the subfields for the ADD select is obtained/ se obtiene el nombre de los subcampos para el select del ADD
    campocompleto=""
    for (i=0;i<Tx[5].length;i++){
        key=Tx[5].substr(i,1)
        key_sc=key
        if (i<2 && indicadores!="") key="I"+key
        if (key in vc){
            c=vc[key].split('$$$$$')
            if (vc[key]!=""){
                for (icc=0;icc<c.length;icc++){
                    campocompleto+="^"+key_sc+c[icc]
                }
            }else{
                campocompleto+="^"+key_sc
            }
        }else{
            campocompleto+="^"+key
        }
    }
    campos=campocompleto.split('^')
    for (i=0;i<Tx[5].length;i++){
        xd=SubCampos[i].split('|')
        if ((xd[5]==1 || xd[5]==2) && i<2 && is_marc=="S"){
            ix="I"+xd[5]      //ADD THE LETTER I TO THE INDICATOR CODE FOR NOT CONFUSING WITH SUBFIELD 2
            Desc_sc[ix]=xd[2]
        }else{
            Desc_sc[xd[5]]=xd[2]
        }
    }
    sc_ant=""
    len=campos.length
    for (i=1;i<len;i++){
	new_subc=campos[i].substr(0,1)
	list_subc=Tx[5].substr(i-1,1)
	pick=""
	// The chracteristics of the subfield are obtained/ SE OBTIENE LAS CARACTERISTICAS DEL SUBCAMPO
	for (j=0;j<Tx[5].length;j++){
		sc=SubCampos[j].split('|')
		tipoe=sc[7]
		Ind=""
		if (i<3 && (sc[5]==1 || sc[5]==2) && is_marc=="S"){
			Ind="I"
		}
		if (sc[5]==new_subc){
			ind_pick=Ind+sc[5]
			if (ind_pick in PickList){
				pick=PickList[ind_pick]
			}
			break
		}
	}

	valor=""

	if (campos[i].length>1){
		valor=campos[i].substr(1)
	}else{
		valor=""
	}
	M++
	html+="<tr onmouseover=\"this.className = 'rowOver';\" onmouseout=\"this.className = '';\">\n"
	html+="<td nowrap valign=top >"
	if (sc[16]=="1" || Trim(sc[17])!=""){
		if (Trim(sc[17])!=""){
			html+=" <a class='bt-fdt' href='javascript:Ayuda(\"\",\""+sc[17]+"\")'><i class=\"fas fa-question\"></i></a>"
		}else{
			html+="<a class='bt-fdt' href=javascript:Ayuda('"+Tx[1]+"_"+new_subc+"','')><i class=\"fas fa-question\"></i></a>"
		}
	}
	html+=new_subc
	C_Sc=new_subc
	strsubc+=new_subc
	lista_sc[M]="t"+C_Sc+"_"+M
	ixsc=Tx[5].indexOf(list_subc)
	if (is_marc!="S" || i>2 || (C_Sc!=1 && C_Sc!=2)){
		if (C_Sc!="-"){
			if (sc_ant!="-"){
				html+=" <a class='bt-fdt' href=javascript:SubirSubc('t"+C_Sc+"_"+M+"') title='"+trn_movesubfup+"'><i class='fas fa-caret-up'></i></a>"
				html+="<a class='bt-fdt' href=javascript:BajarSubc('t"+C_Sc+"_"+M+"') title='"+trn_movesubfdown+"'><i class='fas fa-caret-down'></i></a>"
			}else{
				sc_ant=""
				html+="<a class='bt-fdt' href=javascript:BajarSubc('t"+C_Sc+"_"+M+"') title='"+trn_movesubfup+"'><i class='fas fa-caret-down'></i></a>"
			}
			ixsc=Tx[5].indexOf(new_subc,2)
		}else{
			sc_ant="-"
		}
	}

	if (sc[12]!=""){
		prefijo=sc[12]
		iSc=sc[5]
		Formato=sc[13]
		db_link=sc[11]
		if (db_link=="") {
			db_link=base
		}
		cipar_link=db_link+".par"
		link=" &nbsp;<a href='javascript:AbrirIndiceAlfabetico(\"t"+C_Sc+"_"+M+"\",\""+prefijo+"\",\""+iSc+"\",\"S\""+",\""+db_link+"\""+",\""+cipar_link+"\""+",\""+TagCampo+"\""+",\""+Formato+"\")'"
		link+="title='"+trn_occsearch+"'>"
		link+="<i class=\"fas fa-search\"></i></a>"
		if (sc[7]!="I" && sc[10]=="T") {
			link+="&nbsp;<a href='javascript:AbrirTesauro(\"t"+C_Sc+"_"+M+"\",\""+db_link+"\",\"DE_\",\"0\")'><i class=\"fas fa-cubes\"></i></a>&nbsp;";
		}
	}else{
		link=""
	}
	html+=link
	html+="</td>"
	//GET THE SUBFIELD NAME
	ixnamec=Tx[5].indexOf(C_Sc)
	if (is_marc=="S" && (C_Sc==1 || C_Sc==2) && i<3 ){
		NombreSc=Desc_sc["I"+C_Sc]	//GET THE NAME OF THE INDICATOR
	}else{
		NombreSc=Desc_sc[C_Sc]		//GET THE NAME OF THE SUBFIELD
	}
	html+="<td  valign=top>"+NombreSc+"</a> </td>"
		   html+="<td class=td nowrap>"
		   xsize="70"
		   if (is_marc=="S" && i<3 && (C_Sc==1 || C_Sc==2)){
			   xsize="1 maxlength=1"
			   NamePick="I"+C_Sc
		   }else{
			   NamePick=C_Sc
		   }
	if (link!=""){
		pick=""
	}
	if (pick!=""){
		NombreCampo="t"+C_Sc+"_"+M;
		html+=" <select name="+TagCampo+" id=t"+C_Sc+"_"+M+"><option value=' '> </option>\n"
		opt=pick.split('$$$$')
		selected=""
		if (orgxsalida=="") valor=sc[15] // set default
		for (var ixopt in opt){
			if (Trim(opt[ixopt])!=""){
				o=opt[ixopt].split('|')
				if (Trim(o[1])=="") o[1]=o[0]
				if (Trim(valor)==Trim(o[0]))  selected= " selected"
				if (o[5]!="-") html+="<option value='"+o[0]+"' "+selected+">"+o[1]+"</option>\n"
				selected=""
			}
		}
		html+="</select>\n";
		picklist=NamePickList[NamePick]
		if (act_picklist=="Y"){
			html+= " <a class='bt-fdt' href=\"javascript:AgregarPicklist('"+picklist+"','"+NombreCampo+"','$campo')\"><i class='far fa-edit' title='"+trn_mod_picklist+"'></i></a>"
		}
		html+= " <a class='bt-fdt' href=\"javascript:RefrescarPicklist('"+picklist+"','"+NombreCampo+"','$campo')\"><i class='fas fa-redo' title='"+trn_reload_picklist+"'></i></a> &nbsp; ";
	}else{
		switch (sc[7]){
			case "U":
				//case Upload
				msgupload = "Subir";
				msgseleccionar = "Seleccionar";
				NombreCampo = "t" + C_Sc + "_" + M;

				html += "<div style='display:flex; align-items:flex-start; gap:8px; width:100%;'>";
				html += "<input type=text class=SubC size=" + xsize + " name=" + NombreCampo + " id=t" + C_Sc + "_" + M + " value='" + valor + "' style='flex-grow:1; margin:0;' >";

				html += "<div style='display:flex; flex-direction:column; gap:4px;'>";
				html += " <a class=\"bt-fdt\" style='margin:0;' href=\"javascript:EnviarArchivo('" + NombreCampo + "','" + sc[7] + "')\"><i class=\"fas fa-upload\" title=\"" + msgupload + "\"></i></a>";

				// Usamos a função SelectArchivo que você já corrigiu no scripts_dataentry, em vez do window.open cru
				html += " <a class=\"bt-fdt\" style='margin:0;' href=\"javascript:SelectArchivo('" + NombreCampo + "','forma1')\"><i class=\"far fa-folder-open\" title=\"" + msgseleccionar + "\"></i></a>";
				html += "</div></div>";
				break
			default:
				// default value in case of a new record
				//alert("salida="+orgxsalida+"=")
				if (orgxsalida=="") valor=sc[15]
				if (valor.indexOf("'")==-1)
					html+="<input type=text class=SubC  size="+xsize+" name="+TagCampo+" id=t"+C_Sc+"_"+M+" value='"+valor+"' >&nbsp;&nbsp;"
				else
					html+="<input type=text class=SubC  size="+xsize+" name="+TagCampo+" id=t"+C_Sc+"_"+M+" value=\""+valor+"\" >&nbsp;&nbsp;"+valor
		}
	}
	subc_r="S"
	if (subc_r!="S" || (i>2 || C_Sc!=1 && C_Sc!=2 && subc_r=="S")){
		if (subc_r=="S"){
			idoflink="at"+C_Sc+"_"+M
			html+="<a class='bt-fdt-blue' href=javascript:AgregarSubcampo('"+idoflink+"','"+C_Sc+"') title='"+trn_addoccur+"' id="+idoflink+">"
			html+="<i class='fas fa-plus'></i>"
			html+="</a>"
			html+=" &nbsp;"
			html+="<a class='bt-fdt' href=javascript:DeleteSubfield('t"+C_Sc+"_"+M+"') title='"+trn_deloccur+"'>"
			html+="<i class='far fa-trash-alt'></i>"
			html+="</a>"
		}
	}
    }
    html+="</table>"
    elem = document.getElementById("asubc");
    elem.innerHTML = html;
}

function BajarOcurrencia(id){
    //alert("BajarOcurrencia\nid="+id)
    ix=document.forma1.lista.selectedIndex
    maxindex=document.forma1.lista.options.length
    maxindex--
    if (ix==-1 || ix>=maxindex) return
    ocurren=document.forma1.lista.options[ix+1].value
    txt_ocurren=document.forma1.lista.options[ix+1].text
    document.forma1.lista.options[ix+1].value=document.forma1.lista.options[ix].value
    document.forma1.lista.options[ix+1].text=document.forma1.lista.options[ix].text
    document.forma1.lista.options[ix].value=ocurren
    document.forma1.lista.options[ix].text=txt_ocurren
    document.forma1.lista.selectedIndex=ix+1
}

function SubirOcurrencia(id){
	//alert("SubirOcurrencia\nid="+id)
	ix=document.forma1.lista.selectedIndex
	if (ix==-1 || ix==0) return
	ocurren=document.forma1.lista.options[ix-1].value
	txt_ocurren=document.forma1.lista.options[ix-1].text
	document.forma1.lista.options[ix-1].value=document.forma1.lista.options[ix].value
	document.forma1.lista.options[ix-1].text=document.forma1.lista.options[ix].text
	document.forma1.lista.options[ix].value=ocurren
	document.forma1.lista.options[ix].text=txt_ocurren
	document.forma1.lista.selectedIndex=ix-1
}


function BajarSubc(Id){
	//alert("BajarSubc\nid="+Id)
	valores=Array()
	for (i=0;i<lista_sc.length;i++){
		valores[i]=returnObjById(lista_sc[i]).value
		if (lista_sc[i]==Id){
			xpos=i
		}
	}
	if (xpos==lista_sc.length-1) return
	areemplazar=returnObjById(lista_sc[xpos+1]).value
	valores[xpos+1]=valores[xpos]
	valores[xpos]=areemplazar
	areemplazar=lista_sc[xpos+1]
	lista_sc[xpos+1]=lista_sc[xpos]
	lista_sc[xpos]=areemplazar
	xsalida=""

	for (i=0;i<lista_sc.length;i++){
		subc=lista_sc[i].substr(1,1)
		if ((i==0 || i==1 ) & (subc==1 || subc==2) && is_marc=="S"){
			xsalida+=valores[i]
		}else{
			xsalida+="^"+subc+valores[i]
		}
	}
	Redraw(xsalida,"","")
}

function SubirSubc(Id){
	//alert("SubirSubc\nid="+Id)
	valores=Array()
	for (i=0;i<lista_sc.length;i++){
		ctrl= returnObjById(lista_sc[i])
		valores[i]=returnObjById(lista_sc[i]).value
		if (lista_sc[i]==Id){
			xpos=i
		}
	}
	if (xpos==0) return
 	xxss=lista_sc[xpos-1]
 	if (xxss.substr(1,1)==2 || xxss.substr(1,1)==1 ) return
	areemplazar=returnObjById(lista_sc[xpos-1]).value
	valores[xpos-1]=valores[xpos]
	valores[xpos]=areemplazar
	areemplazar=lista_sc[xpos-1]
	lista_sc[xpos-1]=lista_sc[xpos]
	lista_sc[xpos]=areemplazar
	xsalida=""
	for (i=0;i<lista_sc.length;i++){
		subc=lista_sc[i].substr(1,1)
		if ((i==0 || i==1 ) & (subc==1 || subc==2) && is_marc=="S"){
			xsalida+=valores[i]
		}else{
			xsalida+="^"+subc+valores[i]
		}
	}
	Redraw(xsalida,"","")
}

function AgregarSubcampo(idoflink,subfield){
    //alert("AgregarSubcampo1\idoflink="+idoflink+"\nsubfield="+subfield)
    add_name=""
    ins=idoflink
    //verify if the actual subfield is filled. If not, no subfield is added
    ins=ins.substr(1)
    ctrl= returnObjById(ins)
    if (Trim(ctrl.value)=="") {
	if (ins.substr(1,1)!="-"){   //if the subfield is optional can be empty
  		ins=-1
		alert(trn_fillempty)
  		return
  	}
    }
    salida=""
    ixsc=-1
    for (i=0;i<document.forma1.elements.length;i++){
      	tipo=document.forma1.elements[i].type
      	nombre=""
       	switch (tipo){
        	case "text":
        		ixsc++
        		nombre=document.forma1.elements[i].id
        		subc_act=nombre.substr(1,1)
        		valor=" "
        		valor=document.forma1.elements[i].value
        		if (ixsc>1){
        			salida+="^"+subc_act+valor
        		}else{
        			if ((ixsc==0 || ixsc==1) && (subc_act=="1" || subc_act=="2") && is_marc=="S"){
        				salida+=valor
				}else{
					salida+="^"+subc_act+valor
				}
        		}
			break
	      	case "select-one":
	       		nombre=document.forma1.elements[i].id
	       		if (nombre=="" || nombre.substr(0,1)!="t") break
	       		subc_act=nombre.substr(1,1)
	       		ixsc++
	       		valor=document.forma1.elements[i].options[document.forma1.elements[i].selectedIndex].value
	       		if (ixsc<2 && (subc_act==1 || subc_act==2)){
	       			if (Trim(valor)=="") valor=" "
	       				salida+=valor
	       		}else{
	       			if (Trim(valor)!=""){
						if (valor.indexOf('^')==-1)
							salida+="^"+subc_act+valor
						else
							salida+=valor
					}
	       		}
     			break
        }
       	if (nombre==ins){            // se determina si el nuevo subcampo se va a insertar aquí
       		salida+="^"+subfield
       	}
    }
    Redraw(salida,subfield,add_name)
    return
}

function DeleteSubfield(subc){
	//alert("DeleteSubfield\nsubc="+subc)
	salida=""
	ixsc=-1

    for (i=0;i<document.forma1.elements.length;i++){
    	tipo=document.forma1.elements[i].type
   	nombre=""
       	switch (tipo){
        	case "text":
        		ixsc++
        		nombre=document.forma1.elements[i].id
        		subc_act=nombre.substr(1,1)
        		valor=" "
        		valor=document.forma1.elements[i].value
        		if (nombre!=subc){
	    			if (ixsc>1){
	    				salida+="^"+subc_act+valor
	    			}else{
	    				if ((ixsc==0 || ixsc==1) && (subc_act=="1" || subc_act=="2")){
	    					salida+=valor
	               		}else{
	               			salida+="^"+subc_act+valor
	               		}
	    			}
    			}
            	break
	      	case "select-one":
	      		case "select-one":
	       		nombre=document.forma1.elements[i].id
	       		if (nombre=="" || nombre.substr(0,1)!="t") break
	       		subc_act=nombre.substr(1,1)
	       		ixsc++
	       		valor=document.forma1.elements[i].options[document.forma1.elements[i].selectedIndex].value
	       		if (ixsc<2 && (subc_act==1 || subc_act==2)){
	       			if (Trim(valor)=="") valor=" "
	       				salida+=valor
	       		}else{
	       			if (Trim(valor)!=""){
						if (valor.indexOf('^')==-1)
							salida+="^"+subc_act+valor
						else
							salida+=valor
					}
	       		}
     			break
        }

    }
    Redraw(salida,"","")
    return
}
