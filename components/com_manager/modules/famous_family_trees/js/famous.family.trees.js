function JMBFamousFamilyTreesObject(obj){
	var module = this;

    module.msg = {
        FTT_MOD_FAMOUS_FAMILY_SELECT_A_FAMILY_TREE: "Select a Family Tree",
        FTT_MOD_FAMOUS_FAMILY_FAMILY_MEMBERS: "Family Members",
        FTT_MOD_FAMOUS_FAMILY_LIVING: "Living",
        FTT_MOD_FAMOUS_FAMILY_DESCENDANTS: "Descendants",
        FTT_MOD_FAMOUS_FAMILY_LOGIN_AS: "Login as"
    }

	var getName = function(ind){
		return [(ind.Nick!=null)?ind.Nick:ind.FirstName,(ind.LastName!=null)?ind.LastName:''].join(' ');	
	}
	
	var getAvatar = function(e){
		var sb = host.stringBuffer();
		if(e.avatar){
			return sb._('<img src="index.php?option=com_manager&task=getResizeImage&tree_id=')._(e.tree_id)._('&id=')._(e.avatar.Id)._('&w=50&h=50">').result();
		} else {
			var img_name = (e.individ.Gender=="M")?'male.png':'female.png';
			var img_path  = sb._(module.Path)._("/components/com_manager/modules/famous_family_trees/imgs/")._(img_name).result();
			return sb.clear()._('<img class="jmb-families-avatar view" height="50px" width="50px" src="')._(img_path)._('">').result()
		}
	}
	
	var createBody = function(){
		var sb = host.stringBuffer();
		sb._('<div class="jmb-famous-family-body">');
			sb._('<div class="jmb-famous-family-title"><span>')._(module.msg.FTT_MOD_FAMOUS_FAMILY_SELECT_A_FAMILY_TREE)._('...</span></div>');
			sb._('<div class="jmb-famous-family-content">&nbsp;</div>');
		sb._('</div>');
		return jQuery(sb.result());
	}
	
	var createItem = function(e){
		var sb = host.stringBuffer();
		sb._('<li id="')._(e.tree_id)._('" class="jmb-famous-family-item">');
			sb._('<div class="jmb-famous-family-item-content">');
				sb._('<div id="_info" class="jmb-famous-family-item-cell">')
					sb._('<div class="jmb-famous-family-info-name"><span>')._(e.name)._('</span></div>');
					sb._('<div class="jmb-famous-family-info-content">');
						sb._('<div class="jmb-famous-family-info-title"><span>')._(module.msg.FTT_MOD_FAMOUS_FAMILY_FAMILY_MEMBERS)._(':</span></div>');
						sb._('<div class="jmb-famous-family-info-counts">');
							sb._('<div class="jmb-famous-family-info-living"><span>')._(e.living)._('</span> ')._(module.msg.FTT_MOD_FAMOUS_FAMILY_LIVING)._('</div>');
							sb._('<div class="jmb-famous-family-info-descendants"><span>')._(e.descendants)._('</span> ')._(module.msg.FTT_MOD_FAMOUS_FAMILY_LOGIN_AS)._('</div>');
						sb._('</div>');
					sb._('</div>');
				sb._('</div>');
				sb._('<div id="_avatar" class="jmb-famous-family-item-cell">')._(getAvatar(e))._('</div>');
				sb._('<div id="_login" class="jmb-famous-family-item-cell">')
					sb._('<div><span>')._(module.msg.FTT_MOD_FAMOUS_FAMILY_LOGIN_AS)._(':</span></div>');
					sb._('<div class="jmb-famous-family-login-name"><span>')._(getName(e.individ))._('</span></div>')
				sb._('</div>');
			sb._('</div>');
		sb._('</li>');
		return jQuery(sb.result());
	}
	
	var clickItem = function(item, e){
		jQuery(item).click(function(){
			var args = '{"TreeId":"'+e.tree_id+'","Id":"'+e.individ.Id+'"}';
			module.ajax('setFamilies', args,function(res){
				window.location.reload();
			});
		});		
	}
	
	var createItems = function(json){
		var ul = jQuery('<ul></ul>');
		jQuery(json.families).each(function(i,e){
			var item = createItem(e);
			jQuery(ul).append(item);
			clickItem(item, e);
		});
		return ul;
	}
	
	this.Path = null;	

	this.ajax('getFamilies', null, function(res){
		var json = storage.getJSON(res.responseText);
        if(typeof(json.msg) != 'undefined'){
            module.msg = json.msg;
        }
		module.Path = json.path;
		var body = createBody();
		var ul = createItems(json);
		jQuery(body).find('.jmb-famous-family-content').append(ul);
		jQuery(obj).append(body);
	})
	
}

JMBFamousFamilyTreesObject.prototype = {
	ajax:function(func, params, callback){
        storage.callMethod("famous_family_trees", "JMBFamousFamily", func, params, function(res){
				callback(res);
		})
	}
}



