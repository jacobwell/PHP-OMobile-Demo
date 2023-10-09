if ((navigator.userAgent.indexOf('iPhone') != -1)  || (navigator.userAgent.indexOf('iPod') != -1)) {addEventListener('load', function() {setTimeout(hideURLbar, 0);},false);}
function hideURLbar() {window.scrollTo(0, 1);}

activeMenu = '4';
theActiveTab = 'tab4';
function tabToggler(whichTab) {
	tabName = 'tab' + whichTab;
	document.getElementById(activeMenu).className='notActive';
	document.getElementById(theActiveTab).className='notActiveTab';
	document.getElementById(whichTab).className='active';
	document.getElementById(tabName).className='activeTab';
	activeMenu = whichTab;
	theActiveTab = tabName;
}
function switchState() {
	document.getElementById('title').setAttribute('class', 'menuUpMode');
	document.getElementById('clickTo').innerHTML='(click to show menu)';
}
function selectForm(frm){
	var hiddenForms = document.getElementById("allForms");
	theForm = hiddenForms.getElementsByTagName("form");
	for(x=0; x<theForm.length; x++){theForm[x].style.display = "none";}
	if (theForm[frm-1]){theForm[frm-1].style.display = "block";}
}
var myTabs=new Array('Usage','Costs','Tips','Account');
function menuUp(tabNum) {
	$('#tabholder').delay(200).slideUp(300, function() {
		setTimeout('switchState()', 100);
		document.getElementById('OP').innerHTML='';
		document.getElementById('WER').innerHTML='';
		document.getElementById('onPage').innerHTML= myTabs[tabNum];
	});
	$('#title').delay(100).slideUp(300).delay(100).slideDown(200);
}
function menuDown(){
	$('#tabholder').slideDown(500);
	document.getElementById('title').setAttribute('class', 'menuDownMode');
	document.getElementById('clickTo').innerHTML='';
	document.getElementById('onPage').innerHTML='Mobile';
	document.getElementById('OP').innerHTML='OP';
	document.getElementById('WER').innerHTML='WER ';
}

function hideTime() {
	setTimeout('hideURLbar()'), 1000;
}
