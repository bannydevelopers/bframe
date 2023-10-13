<textarea class="tinymce" id="<?=@$editor_name?>" name="<?=$editor_name?>" style="width: <?=$width?>;min-height:<?=$height?>">
  <?=@$content?>
</textarea>
<style>
    
  /* For other boilerplate styles, see: /docs/general-configuration-guide/boilerplate-content-css/ */
  /*
  * For rendering images inserted using the image plugin.
  * Includes image captions using the HTML5 figure element.
  */
  
  figure.image {
    display: inline-block;
    border: 1px solid gray;
    margin: 0 2px 0 1px;
    background: #f5f2f0;
  }
  
  figure.align-left {
    float: left;
  }
  
  figure.align-right {
    float: right;
  }
  
  figure.image img {
    margin: 8px 8px 0 8px;
  }
  
  figure.image figcaption {
    margin: 6px 8px 6px 8px;
    text-align: center;
  }
  
  
  /*
   Alignment using classes rather than inline styles
   check out the "formats" option
  */
  
  img.align-left {
    float: left;
  }
  
  img.align-right {
    float: right;
  }
  .flex{
      display: flex;
  }
  .flex > *{
      flex-basis: 100%;
  }
  #mypopdiv{
    position: fixed;
    left: calc( ( 100% - 600px ) / 2 );
    right: calc( ( 100% - 600px ) / 2 );
    top: 4em;
    padding: 2px;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: none;
  }
  #mypopdiv > div{
    max-width: 600px;
    max-height: 400px;
    background: #fff;
    border: 1px solid #999;
  }
</style>
<div id="mypopdiv">
  <div>
    <h3 style="background: #f3f3f3;display: flex;">
      <span style="padding:12px 18px;flex-basis:100%"><img src="incs/userfiles/ckeimages/logo.png" height="25"> 
        &nbsp; File manager
      </span>
      <button type="button" id="newmedia">&plus;</button>
      <input type="file" name="newmedia" accept="image/*" style="display: none;">
      <button type="button" onclick="removemypopdiv()">&times;</button>
    </h3>
    <div style="padding: 12px;max-height: 300px;overflow-y: auto;background: #fff;">
      Loading...
    </div>
  </div>
</div>
<script src="vendor/tinymce/tinymce/js/tinymce.min.js"></script>
<script>
  const tinyeditor = document.querySelector('textarea.tinymce');
  if(tinyeditor !== undefined){
    document.body.append(document.getElementById('mypopdiv'));
    var removemypopdiv = function(){
      document.querySelector('#mypopdiv').style.display = 'none';
      document.querySelector('#mypopdiv > div > div').innerHTML = 'Loading...';
    };
    var uploadPhoto = function(){
      console.log(window.event.target.value);
    };
    var useDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var opts = {doc_root:"/",selector:"page_content",height:420};
    tinymce.init({
      selector: 'textarea.tinymce',
      valid_elements : ""
        +"a[accesskey|charset|class|coords|dir<ltr?rtl|href|hreflang|id|lang|name"
        +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|rel|rev"
        +"|shape<circle?default?poly?rect|style|tabindex|title|target|type],"
        +"abbr[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"acronym[class|dir<ltr?rtl|id|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"address[class|align|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"applet[align<bottom?left?middle?right?top|alt|archive|class|code|codebase"
        +"|height|hspace|id|name|object|style|title|vspace|width],"
        +"area[accesskey|alt|class|coords|dir<ltr?rtl|href|id|lang|nohref<nohref"
        +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup"
        +"|shape<circle?default?poly?rect|style|tabindex|title|target],"
        +"base[href|target],"
        +"basefont[color|face|id|size],"
        +"bdo[class|dir<ltr?rtl|id|lang|style|title],"
        +"big[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"blockquote[cite|class|dir<ltr?rtl|id|lang|onclick|ondblclick"
        +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
        +"|onmouseover|onmouseup|style|title],"
        +"body[alink|background|bgcolor|class|dir<ltr?rtl|id|lang|link|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onload|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|onunload|style|title|text|vlink],"
        +"br[class|clear<all?left?none?right|id|style|title],"
        +"button[accesskey|class|dir<ltr?rtl|disabled<disabled|id|lang|name|onblur"
        +"|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|style|tabindex|title|type"
        +"|value],"
        +"caption[align<bottom?left?right?top|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"center[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"cite[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"code[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"col[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
        +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|span|style|title"
        +"|valign<baseline?bottom?middle?top|width],"
        +"colgroup[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl"
        +"|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|span|style|title"
        +"|valign<baseline?bottom?middle?top|width],"
        +"dd[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
        +"del[cite|class|datetime|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"dfn[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"dir[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"div[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"dl[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"dt[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
        +"em/i[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"fieldset[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"font[class|color|dir<ltr?rtl|face|id|lang|size|style|title],"
        +"form[accept|accept-charset|action|class|dir<ltr?rtl|enctype|id|lang"
        +"|method<get?post|name|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onreset|onsubmit"
        +"|style|title|target],"
        +"frame[class|frameborder|id|longdesc|marginheight|marginwidth|name"
        +"|noresize<noresize|scrolling<auto?no?yes|src|style|title],"
        +"frameset[class|cols|id|onload|onunload|rows|style|title],"
        +"h1[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"h2[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"h3[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"h4[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"h5[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"h6[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"head[dir<ltr?rtl|lang|profile],"
        +"hr[align<center?left?right|class|dir<ltr?rtl|id|lang|noshade<noshade|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|size|style|title|width],"
        +"html[dir<ltr?rtl|lang|version],"
        +"iframe[align<bottom?left?middle?right?top|class|frameborder|height|id"
        +"|longdesc|marginheight|marginwidth|name|scrolling<auto?no?yes|src|style"
        +"|title|width],"
        +"img[align<bottom?left?middle?right?top|alt|border|class|dir<ltr?rtl|height"
        +"|hspace|id|ismap<ismap|lang|longdesc|name|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|src|style|title|usemap|vspace|width],"
        +"input[accept|accesskey|align<bottom?left?middle?right?top|alt"
        +"|checked<checked|class|dir<ltr?rtl|disabled<disabled|id|ismap<ismap|lang"
        +"|maxlength|name|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onselect"
        +"|readonly<readonly|size|src|style|tabindex|title"
        +"|type<button?checkbox?file?hidden?image?password?radio?reset?submit?text"
        +"|usemap|value],"
        +"ins[cite|class|datetime|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"isindex[class|dir<ltr?rtl|id|lang|prompt|style|title],"
        +"kbd[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"label[accesskey|class|dir<ltr?rtl|for|id|lang|onblur|onclick|ondblclick"
        +"|onfocus|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
        +"|onmouseover|onmouseup|style|title],"
        +"legend[align<bottom?left?right?top|accesskey|class|dir<ltr?rtl|id|lang"
        +"|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"li[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title|type"
        +"|value],"
        +"link[charset|class|dir<ltr?rtl|href|hreflang|id|lang|media|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|rel|rev|style|title|target|type],"
        +"map[class|dir<ltr?rtl|id|lang|name|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"menu[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"meta[content|dir<ltr?rtl|http-equiv|lang|name|scheme],"
        +"noframes[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"noscript[class|dir<ltr?rtl|id|lang|style|title],"
        +"object[align<bottom?left?middle?right?top|archive|border|class|classid"
        +"|codebase|codetype|data|declare|dir<ltr?rtl|height|hspace|id|lang|name"
        +"|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|standby|style|tabindex|title|type|usemap"
        +"|vspace|width],"
        +"ol[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|start|style|title|type],"
        +"optgroup[class|dir<ltr?rtl|disabled<disabled|id|label|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"option[class|dir<ltr?rtl|disabled<disabled|id|label|lang|onclick|ondblclick"
        +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
        +"|onmouseover|onmouseup|selected<selected|style|title|value],"
        +"p[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|style|title],"
        +"param[id|name|type|value|valuetype<DATA?OBJECT?REF],"
        +"pre/listing/plaintext/xmp[align|class|dir<ltr?rtl|id|lang|onclick|ondblclick"
        +"|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout"
        +"|onmouseover|onmouseup|style|title|width],"
        +"q[cite|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"s[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
        +"samp[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"script[charset|defer|language|src|type],"
        +"select[class|dir<ltr?rtl|disabled<disabled|id|lang|multiple<multiple|name"
        +"|onblur|onchange|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|size|style"
        +"|tabindex|title],"
        +"small[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"span[align<center?justify?left?right|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"strike[class|class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title],"
        +"strong/b[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"style[dir<ltr?rtl|lang|media|title|type],"
        +"sub[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"sup[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title],"
        +"table[align<center?left?right|bgcolor|border|cellpadding|cellspacing|class"
        +"|dir<ltr?rtl|frame|height|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|rules"
        +"|style|summary|title|width],"
        +"tbody[align<center?char?justify?left?right|char|class|charoff|dir<ltr?rtl|id"
        +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
        +"|valign<baseline?bottom?middle?top],"
        +"td[abbr|align<center?char?justify?left?right|axis|bgcolor|char|charoff|class"
        +"|colspan|dir<ltr?rtl|headers|height|id|lang|nowrap<nowrap|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|rowspan|scope<col?colgroup?row?rowgroup"
        +"|style|title|valign<baseline?bottom?middle?top|width],"
        +"textarea[accesskey|class|cols|dir<ltr?rtl|disabled<disabled|id|lang|name"
        +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onselect"
        +"|readonly<readonly|rows|style|tabindex|title],"
        +"tfoot[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
        +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
        +"|valign<baseline?bottom?middle?top],"
        +"th[abbr|align<center?char?justify?left?right|axis|bgcolor|char|charoff|class"
        +"|colspan|dir<ltr?rtl|headers|height|id|lang|nowrap<nowrap|onclick"
        +"|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove"
        +"|onmouseout|onmouseover|onmouseup|rowspan|scope<col?colgroup?row?rowgroup"
        +"|style|title|valign<baseline?bottom?middle?top|width],"
        +"thead[align<center?char?justify?left?right|char|charoff|class|dir<ltr?rtl|id"
        +"|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup|onmousedown"
        +"|onmousemove|onmouseout|onmouseover|onmouseup|style|title"
        +"|valign<baseline?bottom?middle?top],"
        +"title[dir<ltr?rtl|lang],"
        +"tr[abbr|align<center?char?justify?left?right|bgcolor|char|charoff|class"
        +"|rowspan|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title|valign<baseline?bottom?middle?top],"
        +"tt[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
        +"u[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress|onkeyup"
        +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style|title],"
        +"ul[class|compact<compact|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown"
        +"|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover"
        +"|onmouseup|style|title|type],"
        +"var[class|dir<ltr?rtl|id|lang|onclick|ondblclick|onkeydown|onkeypress"
        +"|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|style"
        +"|title]",
      plugins: 'print preview paste importcss searchreplace autolink autosave save directionality '
                .'code visualchars fullscreen image link media table charmap hr nonbreaking '
                .'insertdatetime advlist lists wordcount textpattern noneditable help charmap '
                .'quickbars emoticons',
      menubar: 'file edit view insert format tools help',/* table*/
      toolbar: 'save | undo redo | bold italic underline strikethrough | fontselect | fontsizeselect '
                .'| formatselect | alignleft aligncenter alignright alignjustify | outdent indent '
                .'|  numlist bullist table | image media link | ltr rtl | forecolor backcolor removeformat '
                .'| charmap emoticons | fullscreen code',/*  preview print*/
      toolbar_sticky: true,
      autosave_ask_before_unload: true,
      autosave_interval: '30s',
      autosave_prefix: '{path}{query}-{id}-',
      autosave_restore_when_empty: false,
      autosave_retention: '2m',
      image_advtab: true,
      importcss_append: true,
      image_title: true,
      /* enable automatic uploads of images represented by blob or data URIs*/
      automatic_uploads: true,
      images_upload_url: opts.doc_root + 'wcms/incs/thirdparties/tinymce/filemanager.php',
      images_reuse_filename: true,
      file_picker_types: 'image media',
      /* and here's our custom image picker*/
      file_picker_callback: function (cb, value, meta) {
        let par = document.querySelector('#mypopdiv');
        let poper = document.querySelector('#mypopdiv > div > div');
        par.style.display = 'block';
        document.getElementById('newmedia').addEventListener('click',function(){
          var input = document.createElement('input');
          input.setAttribute('type', 'file');
          if(meta.filetype == 'image'){
            input.setAttribute('accept', 'image/*');
          }
          else if(meta.filetype == 'media'){
            input.setAttribute('accept', 'video/*');
          }

          /*
            Note: In modern browsers input[type="file"] is functional without
            even adding it to the DOM, but that might not be the case in some older
            or quirky browsers like IE, so you might want to add it to the DOM
            just in case, and visually hide it. And do not forget do remove it
            once you do not need it anymore.
          */

          input.onchange = function () {
            var file = this.files[0];

            var reader = new FileReader();
            reader.onload = function () {
              /*
                Note: Now we need to register the blob in TinyMCEs image blob
                registry. In the next release this part hopefully won't be
                necessary, as we are looking to handle it internally.
              */
              var id = 'blobid' + (new Date()).getTime();
              var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
              var base64 = reader.result.split(',')[1];
              var blobInfo = blobCache.create(id, file, base64);
              blobCache.add(blobInfo);

              /* call the callback and populate the Title field with the file name */
              cb(blobInfo.blobUri(), { title: file.name });
              par.style.display = 'none';
              poper.innerHTML = 'Loading...';
            };
            reader.readAsDataURL(file);
          };

          input.click();
        });
        datz = new FormData();
        datz.append('ftype', meta.filetype);
        ajax(
          opts.doc_root+'wcms/incs/thirdparties/tinymce/filemanager.php',
          res=>{
            poper.innerHTML = res;
            poper.addEventListener('click', evt=>{
              if(evt.target.tagName == 'IMG'){
                let src = 'wcms/'+evt.target.getAttribute('src');
                cb(src,{title:evt.target.getAttribute('title'),alt:evt.target.getAttribute('alt')});
                par.style.display = 'none';
                poper.innerHTML = 'Loading...';
              }
            }
            );
          },
          error=>{
            console.log(error);
          },
          datz
        );
      },
      document_base_url: opts.doc_root,
      height: opts.height,
      image_caption: true,
      quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
      noneditable_noneditable_class: 'mceNonEditable',
      toolbar_mode: 'sliding',
      contextmenu: 'link image table',
      skin: useDarkMode ? 'oxide-dark' : 'oxide',
      content_css: useDarkMode ? 'dark' : 'default',
      content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }.flex{display:flex}.flex>*{flex-basis:100%}img{max-width:100%;vertical-align:middle}'
    });
  }

</script>