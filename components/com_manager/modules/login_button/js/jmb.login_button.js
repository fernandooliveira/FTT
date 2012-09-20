function JMBLoginButtonObject(obj){
	var	module = this,
		sb = host.stringBuffer(),
		cont = null;
		
	sb._('<div class="jmb-login-button-container">');
		sb._('<div class="jmb-login-button-title"><span>You must be logged in to access your Family Tree</span></div>');
		sb._('<div class="jmb-login-button">');
			sb._('<div class="jmb-login-button-f">f</div>');
			sb._('<div class="jmb-login-button-body">');
				sb._('<div class="jmb-login-button-u"><span>facebook</span></div>');
				sb._('<div class="jmb-login-button-d"><span>login through facebook</span></div>');
			sb._('</div>')
		sb._('</div>');
	sb._('</div>');
	cont = jQuery(sb.result());
	jQuery(obj).append(cont);
	jQuery(cont).find('.jmb-login-button').click(function(){
        //jfbc.login.login_custom();
        FB.login(function(response){
            if(response.authResponse){
                storage.alert("You are now being logged in using your Facebook credentials", function(){});
                window.location = storage.baseurl+'index.php?option=com_jfbconnect&task=loginFacebookUser&return=myfamily';
            } else {
                alert('Login failed.')
            }
        }, {scope: "user_birthday,user_relationships,email"});
	});

    var setButtonPosition = function(){
        var height = jQuery(window).height();
        var size = (height / 2 - 150)  + 'px';
        jQuery(cont).css('margin-top', size).css('margin-bottom', size);
    }

    jQuery(window).resize(function(){
        setButtonPosition();
    });
    setButtonPosition();

    storage.core.modulesPullObject.unset('JMBLoginButtonObject');
}

JMBLoginButtonObject.prototype = {
	ajax:function(func, params, callback){
        storage.callMethod("login_button", "JMBLoginButtonClass", func, params, function(res){
			callback(res);
		})
	}
}




