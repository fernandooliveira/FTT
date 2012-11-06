<?php
/**
 * @version
 * @package
 * @subpackage
 * @copyright
 * @license
 */
// No direct access.
defined('_JEXEC') or die;

function isFooterPage($alias){
    switch($alias){
        case "about":
        case "conditions":
        case "privacy":
        case "feedback":
        case "help":
        case "contact":
            return true;
        default: return false;
    }
}

function getAlias(){
	$menu   = &JSite::getMenu();
	$active   = $menu->getActive();
	if(is_object($active)){
		return $active->alias;
	}
	return false;
}

$app = JFactory::getApplication();
$base_url = Juri::base();
if(class_exists('FamilyTreeTopHostLibrary')){
    $host = &FamilyTreeTopHostLibrary::getInstance();
    $data = $host->user->get();
} else {
    $data = false;
}

$facebook_id = ( $data ) ? $data->facebookId : 0;
$login_method = ( $data ) ? $data->loginType : '';
$alias = getAlias();


$user_agent = $_SERVER['HTTP_USER_AGENT'];
if (stripos($user_agent, 'MSIE 6.0') !== false
    //|| stripos($user_agent, 'MSIE 8.0') !== false
    || stripos($user_agent, 'MSIE 7.0') !== false
    ){
    if($alias != 'ie'){
        $host = $host->user->setAlias('ie');
        header ("Location: ".$base_url.'index.php/ie');
    }
}
//<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $data->language; ?>" lang="<?php echo $data->language; ?>" dir="<?php echo $this->direction; ?>" >
	<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# website: http://ogp.me/ns/website#">
		<jdoc:include type="head" />
      	    <!-- joomla system stylesheet -->
            <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
        	<!-- fmb template stylesheet -->
            <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/fmb/css/fmb.css" type="text/css"/>
            <!-- fmb template script -->
            <script type="text/javascript" src="<?php echo $this->baseurl ?>/templates/fmb/javascript/fmb.js"></script>
            <!--[if lte IE 7]>
                <style type="text/css">
                    ul li {
                        display: inline
                    }
                </style>
            <![endif]-->
	</head>
	<body _alias="<?php echo $alias; ?>" _baseurl="<?php echo $base_url; ?>" _fb="<?php echo $facebook_id; ?>" _type="<?php echo $login_method; ?>">
        <div id="_content" class="content">
			<div class="header"></div>
			<div class="main">
				<table width="100%">
					<tr>
						<td id="_main" valign="top">
							<div id="fb-root"></div>
							<jdoc:include type="component" />
						</td>
						<td id="_right" valign="top">
                            <div class="right">
                                <?php if($alias=='myfamily'): ?>
                                    <!--<jdoc:include type="modules" name="right" /></div>-->
                                <?php endif; ?>
                        </td>
					</tr>
                    <tr>
                        <td>
                            <div style="display:none;<?php echo (isFooterPage($alias))?'border-top: 1px solid gray;':''; ?>" id="_bottom" class="footer">
                                <div style="left: 0; position: absolute;">
                                    <div><a style="color:black; font-weight: bold;" href="<?php echo $base_url; ?>">FamilyTreeTop.com</a></div>
                                   <!-- <div style="margin-top: 10px;"><span id="siteseal"><script type="text/javascript" src="https://seal.godaddy.com/getSeal?sealID=QbddMchgFRTEtJe2vFw4hjBQe73woVFQRwgBDPdlnAbAKWNkzv7"></script></span></div>-->
                                </div>
                                <div style="right: 0; position: absolute;">
                                    <div>
                                        <ul>
                                            <li><a href="<?php echo $base_url; ?>index.php/about">About</a></li>
                                            <li><a href="<?php echo $base_url; ?>index.php/conditions">Terms & Conditions</a></li>
                                            <li><a href="<?php echo $base_url; ?>index.php/privacy">Privacy Policy</a></li>
                                            <li><a href="<?php echo $base_url; ?>index.php/feedback">Provide Feedback</a></li>
                                            <li><a href="<?php echo $base_url; ?>index.php/contact">Contact</a></li>
                                            <li><a href="<?php echo $base_url; ?>index.php/help">Help</a></li>
                                        </ul>
                                    </div>
                                    <!--<div style="margin-top:15px;">© 2012 Family TreeTop</div>-->
                                </div>
                            </div>
                        </td>
                    </tr>
				</table>
			</div>
        </div>
        <?php if($alias=='myfamily'): ?>
            <div class="slide-out-div">
                <a class="handle" href="http://link-for-non-js-users.html">Content</a>
                <div id="jmb_feedback_form">
                    <div style="display:none;" class="likes">
                        <!-- AddThis Button BEGIN -->
                        <script>
                            if(window == window.top){
                                (function(w){
                                    var head = document.getElementsByTagName("head");
                                    var script = document.createElement("script");
                                    script.src = "http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f97ad88304db623";
                                    script.type="text/javascript";
                                    head[0].appendChild(script);
                                })(window)
                            }
                        </script>
                        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                            <div class="message"></div>
                            <div class="facebook"><a class="addthis_button_facebook at300b"></a></div>
                            <div class="twitter"><a class="addthis_button_twitter at300b"></a></div>
                            <div class="email"><a class="addthis_button_email at300b"></a></div>
                        </div>
                        <!-- AddThis Button END -->
                    </div>
                </div>
            </div>
            <script>
                (function(w){
                    jQuery(".slide-out-div").tabSlideOut({
                        tabHandle: '.handle',
                        pathToTabImage: '../components/com_manager/modules/feedback/images/feedback.gif',
                        imageHeight: '279px',
                        imageWidth: '40px',
                        tabLocation: 'left',
                        speed: 300,
                        action: 'click',
                        topPos: '50px',
                        leftPos: '20px',
                        fixedPosition: false
                    });
                })(window)
            </script>
        <?php endif; ?>
        <script>window.jQuery || document.write('<script src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/javascript/jquery-1.8.1.min.js"><\/script>')</script>
        <script>
            (function(w){
                if(window == window.top){
                    if(jQuery.browser.msie && parseInt(jQuery.browser.version) <= 7){
                        jQuery('div.footer').hide();
                    } else {
                        if('undefined' === typeof(storage)){
                            var alias = '<?php echo $alias; ?>';
                            if('feedback' === alias){
                                jQuery('form#adminForm').ready(function(){
                                    jQuery('form#adminForm').find('h2').remove();
                                    var select = jQuery('form#adminForm').find('.forum-select');
                                    jQuery(select).css('text-align', 'center');
                                    jQuery(select).css('margin', '10px');
                                    jQuery(select).find('div').css('float', 'none');
                                    jQuery.ajax({
                                        url:"index.php?option=com_manager&task=getLanguage&module_name=feedback",
                                        type:"GET",
                                        dataType: "html",
                                        complete : function (req, err) {
                                            var json = jQuery.parseJSON(req.responseText);
                                            var string  = json.FTT_MOD_FEEDBACK_WELCOM.replace('%%', json.FTT_MOD_FEEDBACK_PUBLIC_BETA);
                                            jQuery(select).before('<div style="font-size: 16px;font-weight: bold;margin: 30px;">'+string+'</div>');
                                        }
                                    });
                                });
                            }
                            jQuery('div.footer').show();
                        } else {
                            storage.core.modulesPullObject.bind(function(object){
                                jQuery('div.footer').show();
                            });
                        }
                    }
                }
                if(typeof(storage) == 'undefined'){
                    var tmb = new JMBTopMenuBar();
                    tmb.init();
                }

                jQuery('.scsocialbuttons').remove();
            })(window)
        </script>
        <script type="text/javascript">
            (function(w){
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', 'UA-32469950-1']);
                _gaq.push(['_trackPageview']);

                (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();
            })(window)
        </script>
        <script>// HOTFIX: We can't upgrade to jQuery UI 1.8.6 (yet)
                // This hotfix makes older versions of jQuery UI drag-and-drop work in IE9
                if(jQuery.ui)(function(jQuery){var a=jQuery.ui.mouse.prototype._mouseMove;jQuery.ui.mouse.prototype._mouseMove=function(b){if(jQuery.browser.msie&&document.documentMode>=9){b.button=1};a.apply(this,[b]);}}(jQuery));
        </script>
        <script>
            (function() {
                var ua = navigator.userAgent,
                    iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
                    typeOfCanvas = typeof HTMLCanvasElement,
                    nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'),
                    textSupport = nativeCanvasSupport
                        && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
                //I'm setting this based on the fact that ExCanvas provides text support for IE
                //and that as of today iPhone/iPad current text support is lame
                labelType = (!nativeCanvasSupport || (textSupport && !iStuff))? 'Native' : 'HTML';
                nativeTextSupport = labelType == 'Native';
                useGradients = nativeCanvasSupport;
                animate = !(iStuff || !nativeCanvasSupport);
            })();
        </script>
        <script>
            (function($)
            {
                $(function()
                {
                    var placeholder_support = !!('placeholder' in document.createElement( 'input' ));
                    if (!placeholder_support)
                    {
                        var body = $(document.body);
                        $('input[placeholder]').each(function(){
                            var tpl = '<div class="placeholder" style="position:absolute;overflow:hidden;white-space:nowrap"/>',
                                th = $(this),
                                position = th.offset(),
                                height = th.height(),
                                width = th.width(),
                                placeholder = $(tpl).appendTo(body)
                                    .css({
                                        top: position.top,
                                        left: position.left,
                                        width: width,
                                        height: height,
                                        padding: ((th.innerHeight(true) - height) / 2) + 'px ' +  ((th.innerWidth(true) - width) / 2) + 'px '
                                    })
                                    .text(th.attr('placeholder'))
                                    .addClass(th.attr('class'))
                                ;

                            placeholder.bind('click focus', function(){placeholder.hide();th.focus();});
                            th.bind('blur', function(){if (th.val() == '') placeholder.show()});
                        });
                    }
                });
            }(jQuery));
        </script>
    </body>
</html>