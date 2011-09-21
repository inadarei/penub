$('document').ready(function(){
  
  alert("hello");
  
  
  function createTOC() {
  	var y = document.createElement('div');
  	y.id = 'innertoc';
  	var a = y.appendChild(document.createElement('span'));
  	a.onclick = showhideTOC;
  	a.id = 'contentheader';
  	a.innerHTML = 'show page contents';
  	var z = y.appendChild(document.createElement('div'));
  	z.onclick = showhideTOC;
  	var toBeTOCced = getElementsByTagNames('h2,h3,h4,h5');
  	if (toBeTOCced.length < 2) return false;

  	for (var i=0;i<toBeTOCced.length;i++) {
  		var tmp = document.createElement('a');
  		tmp.innerHTML = toBeTOCced[i].innerHTML;
  		tmp.className = 'page';
  		z.appendChild(tmp);
  		if (toBeTOCced[i].nodeName == 'H4')
  			tmp.className += ' indent';
  		if (toBeTOCced[i].nodeName == 'H5')
  			tmp.className += ' extraindent';
  		var headerId = toBeTOCced[i].id || 'link' + i;
  		tmp.href = '#' + headerId;
  		toBeTOCced[i].id = headerId;
  		if (toBeTOCced[i].nodeName == 'H2') {
  			tmp.innerHTML = 'Top';
  			tmp.href = '#top';
  			toBeTOCced[i].id = 'top';
  		}
  	}
  	return y;
  }

  var TOCstate = 'none';

  function showhideTOC() {
  	TOCstate = (TOCstate == 'none') ? 'block' : 'none';
  	var newText = (TOCstate == 'none') ? 'show page contents' : 'hide page contents';
  	document.getElementById('contentheader').innerHTML = newText;
  	document.getElementById('innertoc').lastChild.style.display = TOCstate;
  }
});