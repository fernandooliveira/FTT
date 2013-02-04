(function($, $ftt){
    $ftt.module.create("MOD_ANCESTORS", function(name, parent, ajax, renderType, popup){
        var	module = this, $fn,
            cont = $('<div id="jit" class="jmb-ancestors-jit"></div>'),
            home_button = $('<div class="jmb-ancestors-home"></div>'),
            json, parse;

        $fn = {
            ajax:function(func, params, callback){
                storage.callMethod("ancestors", "JMBAncestors", func, params, function(res){
                    callback(res);
                })
            },
            getMsg:function(n){
                var t = 'FTT_MOD_ANCESTORS_'+n.toUpperCase();
                if(typeof(module.msg[t]) != 'undefined'){
                    return module.msg[t];
                }
                return '';
            },
            setMsg:function(msg){
                var module = this;
                for(var key in module.msg){
                    if(typeof(msg[key]) != 'undefined'){
                        module.msg[key] = msg[key];
                    }
                }
                return true;
            },
            avatar:function(el){
                return storage.usertree.avatar.get({
                    object:el,
                    width:72,
                    height:80
                });
            },
            click:function(label, node){
                var	sub,
                    id,
                    object,
                    tree;
                return {
                    arrow:function(){
                        sub = this;
                        $(label).find('.jit-node-arrow').click(function(){
                            id = $(this).attr('id');
                            module.targetNode = id;
                            module.st.onClick(id);
                        });
                    },
                    photo:function(){
                        object = $(label).find('div.photo');
                        $(object).mouseenter(function(){
                            $(label).find('.jit-edit-button').addClass('hover');
                            $(label).find('.jit-facebook-icon').addClass('hover');
                        }).mouseleave(function(){
                                $(label).find('.jit-edit-button').removeClass('hover');
                                $(label).find('.jit-facebook-icon').removeClass('hover');
                            });
                        module.fn.mod("tooltip").render('view', {
                            button_facebook:false,
                            button_edit:false,
                            gedcom_id:node.id.split('_')[1],
                            target:object,
                            afterEditorClose:function(){
                                module.fn.mod("tooltip").cleaner(function(){
                                    $fn.render();
                                });
                            }
                        });
                    },
                    edit:function(){
                        object = $(label).find('.jit-edit-button');
                        module.fn.mod("tooltip").render('edit', {
                            button_edit:false,
                            button_facebook:false,
                            gedcom_id:node.id.split('_')[1],
                            target:object,
                            afterEditorClose:function(){
                                module.fn.mod("tooltip").cleaner(function(){
                                    $fn.render();
                                });
                            }
                        });
                    },
                    facebook:function(){
                        $(label).find('.jit-facebook-icon').click(function(){
                            var id = $(this).attr('id');
                            window.open(['http://www.facebook.com/profile.php?id=',id].join(''),'new','width=320,height=240,toolbar=1');
                            return false;
                        });
                    },
                    add:function(){
                        $(label).find('a').click(function(){
                            var id = $(this).parent().attr('id');
                            var add = storage.profile.add({
                                object:storage.usertree.pull[id],
                                events:{
                                    afterEditorClose:function(){
                                        module.fn.mod("tooltip").cleaner(function(){
                                            $fn.render();
                                        });
                                    }
                                }
                            });
                            add.parent().init();
                            return false;
                        });
                    },
                    init:function(){
                        sub = this;
                        if(node.id[0]!=='_'){
                            sub.arrow();
                            sub.photo();
                            sub.edit();
                            sub.facebook();
                        } else {
                            sub.add();
                        }
                    }
                }
            },
            nullNode:function(node){
                var sb = module.fn.stringBuffer();
                sb._('<div id="')._(_getObject(node))._('" class="jit-node-item-question">');
                if(storage.usertree.permission != "GUEST"){
                    sb._('<a href="/add_this_person" onclick="return false;">');
                    sb._($fn.getMsg('add_this_person'));
                    sb._('</a>');
                }
                sb._('</div>');
                return sb.result();
                function _getObject(n){
                    var adj = n.adjacencies;
                    for(var key in adj){
                        if(adj.hasOwnProperty(key)){
                            return key.split('_')[1];
                        }
                    }
                }
            },
            node:function(label, node){
                var	sb = storage.stringBuffer(),
                    data = node.data.ftt_storage,
                    parse,
                    place,
                    prew,
                    object,
                    fam_opt;

                if(!data.is_exist) return $fn.nullNode(node);
                parse = data.parse;
                object = data.object;
                fam_opt = storage.family_line.get.opt();

                prew = function(){
                    var	id = node.data.ftt_storage.prew,
                        objects = module.objects;
                    if(id){
                        return (objects[id].prew)?objects[id].prew:id;
                    }
                    return 0;
                }

                event_string = function(type){
                    var p = parse.place(type);
                    var year = parse[type]('year');
                    var city = (p.city!=null)?p.city:'';
                    var country = (p.country!=null)?p.country:'';
                    if(p.length!=0){
                        if(city.length != 0 && country.length != 0){
                            return year+' ('+city+','+country.substr(0,3)+')';
                        } else if(city.length == 0 && country.length != 0){
                            return year+' ('+country.substr(0,3)+')';
                        } else if(city.length != 0 && country.length == 0){
                            return year+' ('+city+')';
                        } else {
                            return year;
                        }
                    }
                    return year;
                }

                sb._('<div id="father_line" class="line-without-border">')
                sb._('<div id="mother_line" class="line-without-border">')
                sb._('<div class="jit-node-item">');
                sb._('<table>');
                sb._('<tr>');
                sb._('<td>');
                sb._('<div id="')._(parse.gedcom_id)._('-view" class="photo">')._($fn.avatar(object));
                if(!module.loggedByFamous && parse.is_editable){
                    sb._('<div id="')._(parse.gedcom_id)._('-edit" class="jit-edit-button">&nbsp;</div>');
                }
                if(parse.facebook_id != '0'){
                    sb._('<div class="jit-facebook-icon" id="')._(parse.facebook_id)._('">&nbsp;</div>');
                }
                if(parse.is_death){
                    sb._('<div class="jit-death-marker">&nbsp;</div>');
                }
                sb._('</div>');
                sb._('</td>');
                sb._('<td valign="top"><div class="data')._((parse.gender=='M')?' male':' female')._('">')
                sb._('<div class="name">')._(parse.name)._('</div>');
                if(parse.is_birth){
                    place = parse.place('birth');
                    sb._('<div class="birt">B: ')._(event_string('birth'))._('</div>');
                }
                if(parse.is_death){
                    place = parse.place('death');
                    sb._('<div class="deat">D: ')._(event_string('death'))._('</div>');
                }
                if(parse.relation){
                    sb._('<div class="relation">')._(parse.relation)._('</div>');
                }
                sb._('</div></td>')
                sb._('</tr>')
                sb._('</table>');
                sb._('<div id="')._(prew(node.data.ftt_storage.prew))._('" class="jit-node-arrow left">&nbsp;</div>');
                sb._('<div id="')._((node.data.ftt_storage.next?node.data.ftt_storage.next:0))._('" class="jit-node-arrow right">&nbsp;</div>');
                sb._('</div>');
                sb._('</div></div>');
                return sb.result();
            },
            getTree:function(ch){
                var	user = module.user,
                    usertree = module.usertree,
                    tree = {},
                    count = 0,
                    get_parents,
                    get_parents_id,
                    set_data,
                    set_null_data,
                    set_ancestors,
                    getPfexix;

                getPfexix = function(prf, id, count){
                    if('undefined' === typeof(count)){
                        return prf+'_'+id;
                    } else{
                        return prf+'_'+id+'_'+count;
                    }
                }

                get_parents = function(id){
                    if(id[0]==='_'||!usertree[id.split('_')[1]]) return false;
                    return usertree[id.split('_')[1]].parents;
                }

                get_parents_id = function(par){
                    var first, key;
                    for(key in par){
                        first = par[key];
                        break;
                    }
                    return [(first.mother!=null)?first.mother.gedcom_id:false,(first.father!=null)?first.father.gedcom_id:false];
                }

                set_data = function(id, prew){
                    if(!usertree[id]) return set_null_data();
                    var	object = usertree[id],
                        parse = storage.usertree.parse(object),
                        node;

                    node = {
                        id: getPfexix(module.prefix, parse.gedcom_id),
                        name: parse.name,
                        data:{
                            ftt_storage:{
                                object:object,
                                parse:parse,
                                prew:(prew)?prew.id:false,
                                is_exist:true
                            }
                        },
                        children:[]
                    }
                    module.objects[getPfexix(module.prefix, parse.gedcom_id)] = { id:parse.gedcom_id, prew:(prew)?prew.id:false }
                    return node;
                }

                set_null_data = function(){
                    count++;
                    return {
                        id: getPfexix('', module.prefix, count),
                        name:'',
                        data:{ ftt_storage:{ is_exist:false} },
                        children:[]
                    }
                }

                set_ancestors = function(el){
                    var	ind = el.id,
                        parents = get_parents(ind),
                        ids;
                    if(parents&&parents.length!=0){
                        ids = get_parents_id(parents);
                        el.children.push(set_data(ids[0], el));
                        el.children.push(set_data(ids[1], el));
                        if(ids){
                            el.data.ftt_storage.next = ind;
                        }
                        if(ids[0]){
                            el.children[0] = set_ancestors(el.children[0]);
                        }
                        if(ids[1]){
                            el.children[1] = set_ancestors(el.children[1]);
                        }
                    } else {
                        el.children.push(set_null_data());
                        el.children.push(set_null_data());
                    }
                    return el;
                }

                var userObject = set_data(ch.user.gedcom_id);
                tree = set_ancestors(userObject);
                return tree;
            },
            init:function(callback){
                var	st,
                    click;

                //Create a new ST instance
                st = new $jit.ST({
                    injectInto: 'jit',
                    levelsToShow: 2,
                    levelDistance: 30,
                    offsetX:240,
                    offsetY:0,
                    duration: 800,
                    //transition: $jit.Trans.Quart.easeInOut,
                    transition: $jit.Trans.Quint.easeOut,
                    Node: {
                        height: 84,
                        width: 214,
                        type: 'rectangle',
                        color:'#C3C3C3',
                        lineWidth: 2,
                        align:"center",
                        overridable: true
                    },
                    Edge: {
                        type: 'bezier',
                        lineWidth: 2,
                        color:'#999',
                        overridable: true
                    },
                    onBeforeCompute: function(node){
                        //overlay
                        var	width = $(module.parent).width(),
                            height  = $(module.parent).height();
                        jQuery(module.overlay).css('width', width+'px').css('height', height+'px');
                        jQuery(module.parent).append(module.overlay);
                        //set spouse
                        var parent = node.getParents();
                        if(parent.length!=0){
                            var ptree = $jit.json.getSubtree(module.tree, parent[0].id);
                            var id = (ptree.children[0].id != node.id)?ptree.children[0].id:ptree.children[1].id;
                            module.spouse = module.st.graph.getNode(id);
                        } else {
                            module.spouse = null;
                        }

                        //set active nodes
                        var subtree = $jit.json.getSubtree(module.tree, node.id);
                        var nodes = {};
                        var set_node = function(tr, level){
                            if(level == 3) return false;
                            nodes[tr.id] = tr.id;
                            if(tr.children!=0){
                                set_node(tr.children[0], level + 1);
                                set_node(tr.children[1], level + 1);
                            }
                        }
                        set_node(subtree, 0);
                        module.nodes = nodes;
                    },
                    onAfterCompute: function(){
                        $(module.overlay).remove();
                    },
                    onCreateLabel: function(label, node){
                        label.id = node.id;
                        label.innerHTML = $fn.node(label, node);
                        click = $fn.click(label, node);
                        click.init();
                        module.nodePull.push({"node":node,"label":label});
                    },
                    onPlaceLabel: function(label, node){
                        var	left = jQuery(label).find('div.jit-node-arrow.left'),
                            right = jQuery(label).find('div.jit-node-arrow.right'),
                            data = node.data.ftt_storage,
                            active = module.st.clickedNode.id,
                            mod = node._depth%2;

                        jQuery(left).show();
                        jQuery(right).show();
                        if(!data.prew || mod!=0 || node.id != active){
                            jQuery(left).hide();
                        }
                        if(mod || node.id == active){
                            jQuery(right).hide();
                        }
                        if(data.object && data.object.parents == null){
                            jQuery(right).hide();
                        }

                        if(node.id in module.nodes){
                            jQuery(label).css('visibility', 'visible');
                        } else {
                            jQuery(label).css('visibility', 'hidden');
                        }
                    },
                    onBeforePlotNode:function(node){
                        if(node.id in module.nodes){
                            node.data.$color = "#C3C3C3"
                        } else {
                            node.data.$color = module.nodeBackgound;
                        }
                    },
                    onBeforePlotLine:function(adj){
                        adj.data.$color = "#EDF0F8";
                        if(adj.nodeTo.id in module.nodes && adj.nodeFrom.id in module.nodes){
                            adj.data.$color = "#999";
                        }
                    }
                });

                module.st = st;

                //load json data
                st.loadJSON(module.tree);
                //compute node positions and layout
                st.compute();
                //emulate a click on the root node.
                st.onClick(st.root, {
                    onComplete:function() {
                        if(callback){
                            callback();
                        }
                    }
                });

                module.targetNode = st.root;
            },
            render:function(){
                var	module = this,
                    st = module.st;

                module.tree = $fn.getTree(module.user);

                //load json data
                st.loadJSON(module.tree);
                //compute node positions and layout
                st.compute();
                //emulate a click on the root node.
                st.select(module.targetNode);
            }
        }

        $(parent).append(cont);
        $(parent).append(home_button);

        module.parent = parent;
        module.nodePull = [];
        module.overlay = $('<div class="jmb-ancestors-overlay">&nbsp;</div>');
        module.path = $FamilyTreeTop.global.base+"components/com_manager/modules/ancestors/";
        module.imagePath = module.path+'images/';
        module.container = cont;
        module.home = home_button;
        module.tree = null;
        module.usertree = null;
        module.user = null;
        module.st = null;
        module.objects = {};
        module.spouse = null;
        module.nodes = null;
        module.clickNode = null;
        module.targetNode = null;
        module.loggedByFamous = parseInt($(document.body).attr('_type'));
        module.prefix = 'jit'+((new Date()).valueOf());

        module.msg = {
            FTT_MOD_ANCESTORS_ADD_THIS_PERSON: "Add this person"
        }

        $(home_button).click(function(){
            if(module.user==null) return false;
            module.st.select(module.st.root);
            return false;
        })

        storage.family_line.bind('JMBAncestorsObject', function(res){
            var nodes = module.nodePull;
            for(var index in nodes){
                var el = nodes[index];
                var data = el.node.data.ftt_storage;
                var label = el.label;
                var user = (data.is_exist)?data.object.user:false;
                if(!user) continue;
                var type = 'is_'+res._line+'_line';
                if(user[type]){
                    var selector = 'div#'+res._line+'_line';
                    if(res._active){
                        $(label).find(selector).addClass(res._line);
                    } else {
                        $(label).find(selector).removeClass(res._line);
                    }
                }
            }
        });

        module.usertree = storage.usertree.pull;
        module.user = module.usertree[storage.usertree.gedcom_id];
        module.tree = $fn.getTree(module.user);
        module.nodeBackgound = (function(){
            var rgb = jQuery('.tab_content').css('backgroundColor');
            var hex = rgb.match(/^#[0-9a-f]{3,6}$/i);
            var parts = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if(parts == null){
                return rgb;
            }
            return _rgbToHex(parts);
            function _rgbToHex(p){
                delete(p[0]);
                for (var i = 1; i <= 3; ++i) {
                    p[i] = parseInt(p[i]).toString(16);
                    if (p[i].length == 1) p[i] = '0' + p[i];
                }
                return '#' + p.join('');
            }
        })()

        $(module.parent).ready(function(){
            (function(){
                var loader = function(){
                    if(jQuery('#jit').length != 0 && typeof($jit.ST) === 'function'){
                        $fn.init(function(){
                            storage.core.modulesPullObject.unset('JMBAncestorsObject');
                        });
                    } else {
                        setTimeout(function(){
                            loader();
                        }, 250)
                    }
                }
                loader();
            })()
        });
        return this;
    });
})(jQuery, $FamilyTreeTop);

function JMBAncestorsObject(obj, popup){
    $FamilyTreeTop.module.init("MOD_ANCESTORS", obj, $FamilyTreeTop.fn.mod("ajax"), "desctop", popup);
}


