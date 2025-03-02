$FamilyTreeTop.create("members", function($){
    'use strict';
    var $this = this,
        $users,
        $box = $('#membersTable'),
        $filter = $('#filterMembers'),
        $relPull = {"immediate_family":[], "grandparents":[], "grandchildren":[], "cousins":[], "in_laws":[], "unknown":[]},
        $pull = {},
        $sort = false,
        $isGender = true,
        $isLiving = true,
        $isMembers = true,
        $isRegistered = true,
        $lastOrderType = false,
        $fn;

    $fn = {
        getUserArray: function(users){
            var result = [];
            for(var prop in users){
                if(!users.hasOwnProperty(prop)) continue;
                var user = $this.mod('usertree').user(users[prop].gedcom_id);
                result.push(user);
            }
            return result;
        },
        setRelPullObject: function(object){
            var relId = object.relationId, inLaw = object.inLaw;
            if(inLaw){
                $relPull["in_laws"].push(object);
            } else if(relId > 0 && relId < 13 && relId != 9){
                $relPull["immediate_family"].push(object);
            } else if(relId == 103 || relId == 104 || relId == 203 || relId == 204){
                $relPull["grandparents"].push(object);
            } else if(relId == 105 || relId == 106 || relId == 205 || relId == 206){
                $relPull["grandchildren"].push(object);
            } else if(relId == 9){
                $relPull["cousins"].push(object);
            } else {
                $relPull["unknown"].push(object);
            }
        },
        setButtonSize:function(){
            var buttons, length = 0, width = 0, item;
            buttons = $($filter).find('[familytreetop-button="members"]');
            $(buttons).each(function(i,e){
                var text = $(e).text();
                if(text.length > length){
                    length = text.length;
                    item = e;
                }
            });
            width = $this.textWidth($(item).text(), "14px 'Helvetica Neue', Helvetica, Arial, sans-serif") + 25;
            $(buttons).each(function(i,e){ $(e).css('width', width + 'px'); });
        },
        createFilterList: function(){
            var ul = $($filter).find('ul'), prop, object, li;
            for(prop in $relPull){
                if(!$relPull.hasOwnProperty(prop)) continue;
                object = $relPull[prop];
                li = $(ul).find('[familytreetop="'+prop+'"]');
                $(li).find('[familytreetop="count"]').text(object.length);
            }
        },
        orderByRelation: function(a,b){
            var _a = parseInt(a.relationId), _b = parseInt(b.relationId);
            if(_a == 0 && _b == 0){
                return 0;
            } else if(_a != 0 && _b == 0){
                return -1
            } else if(_a == 0 && _b != 0){
                return 1;
            } else if(_a < _b){
                return -1;
            } else if(_a > _b){
                return 1;
            } else if(_a == _b){
                return 0;
            }
        },
        orderByName: function(a,b){
            var compA = a.name().toUpperCase();
            var compB = b.name().toUpperCase();
            return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
        },
        orderByYear: function(a,b){
            var adate = _getDate_(a);
            var bdate = _getDate_(b);
            var a = new Date(adate[0], adate[1], adate[2]);
            var b = new Date(bdate[0], bdate[1], bdate[2]);
            return a>b?-1:a<b?1:0;
            function _getDate_(object){
                var event = object.birth();
                if($this.parseBoolean(event)){
                    var date = event.date;
                    return [
                        (date.start_year!=null)?date.start_year:100,
                        (date.start_month!=null)?date.start_month:1,
                        (date.start_day!=null)?date.start_day:1
                    ];
                }
                return [100,1,1];
            }
        },
        orderByPlace: function(a,b){
            var longString = "ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ";
            return _localeCompare_(_getPlace_(a), _getPlace_(b), 0);
            function _localeCompare_(obj1,obj2, level){
                if(level == 3) return 0;
                var res = obj1[level].localeCompare(obj2[level]);
                if(!res){
                    return _localeCompare_(obj1, obj2, level+1);
                }
                return res;
            }
            function _getPlace_(object){
                var event = object.birth();
                if($this.parseBoolean(event)){
                    var place = event.place;
                    return [
                        place.country!=null&&place.country.length>0?place.country.toUpperCase():longString,
                        place.state!=null&&place.state.length>0?place.state.toUpperCase():longString,
                        place.city!=null&&place.city.length>0?place.city.toUpperCase():longString
                    ];
                }
                return [longString,longString,longString];
            }
        },
        order: function(type){
            var orderType = 'orderBy'+type;
            if(orderType == $lastOrderType) return false;
            $users.sort(function(a,b){
                return $fn['orderBy'+type](a,b);
            });
            $lastOrderType = orderType;
        },
        isSortable: function(object){
            if(!$sort){
                return true;
            } else if("undefined" !== typeof($sort["unknown"]) && !object.relationId){
                return true;
            } else if("undefined" !== typeof($sort[1000]) && object.inLaw){
                return true;
            } else if (object.relationId in $sort && !object.inLaw) {
                return true;
            }
            return false;
        },
        isGender: function(object){
            if("object" == typeof($isGender)){
                return (object.gender == $isGender['gender']);
            } else {
                return true;
            }
        },
        isLiving: function(object){
            if("object" == typeof($isLiving)){
                return (object.isAlive() == $isLiving['alive']);
            } else {
                return true;
            }
        },
        isRegistered: function(object){
            if("object" == typeof($isRegistered)){
                if(object.facebook_id != 0 && $isRegistered['registered']){
                    return true
                } else if(object.facebook_id != 0 && !$isRegistered['registered']){
                    return false;
                } else if(object.facebook_id == 0 && $isRegistered['registered']){
                    return false;
                } else if(object.facebook_id == 0 && !$isRegistered['registered']){
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        },
        isMembers: function(object, aList, dList){
            if("object" == typeof($isMembers)){
                var list = ($isMembers['ancestors'])?aList:dList;
                return ("undefined" !== typeof(list[object.gedcom_id]));
            } else {
                return true;
            }
        },
        render: function(order){
            var key, object, birth, tr,td, avatar, ancestorList, descendantList;
            $($box).find('tbody tr').remove();
            if("undefined" === typeof(order)){
                $users.sort(function(a,b){
                    if(a.facebook_id == 0 && b.facebook_id == 0){
                        return 0;
                    } else if(a.facebook_id != 0 && b.facebook_id == 0){
                        return -1;
                    } else if(a.facebook_id == 0 && b.facebook_id != 0){
                        return 1;
                    }
                });
            }
            ancestorList = $this.mod('usertree').getAncestorList();
            descendantList = $this.mod('usertree').getDescendantList();
            for (key in $users){
                if(!$users.hasOwnProperty(key)) continue;
                object = $users[key];
                birth = object.birth();
                tr = $('<tr class="familytreetop-hover-effect" gedcom_id="'+object.gedcom_id+'"></tr>');
                if($fn.isSortable(object)&&$fn.isGender(object)&&$fn.isLiving(object)&&$fn.isRegistered(object)&&$fn.isMembers(object, ancestorList, descendantList)){
                    avatar = object.avatar(["35","35"]);
                    $fn.setRelPullObject(object);
                    $(tr).append('<td><i class="fa fa-leaf"></i> '+object.relation+'</td>');
                    td = $('<td style="'+getPadding(avatar)+'" data-familytreetop-color="'+object.gender+'" gedcom_id="'+object.gedcom_id+'"></td>');
                    if($this.mod('usertree').isAvatar(avatar)){
                        var div = $(document.createElement('div'));
                        $(div).addClass('pull-left');
                        $(div).append(avatar);
                        $(td).append(div);
                    }
                    $(td).append('<div class="pull-left" style="'+getMaxWidth(avatar)+'"> <span style="cursor:pointer;">'+object.name()+'</span></div>');
                    $(tr).append(td);
                    $(tr).append('<td style="width:120px;">'+$this.mod('usertree').parseDate(birth.date)+'</td>');
                    $(tr).append('<td style="text-align: right;">'+$this.mod('usertree').parsePlace(birth.place)+'</td>');
                    $($box).append(tr);
                    $this.mod('popovers').render({
                        target: $(tr).find('td[gedcom_id]')
                    });
                    $this.mod('familyline').bind(tr, object.gedcom_id);
                }
            }
            return true;
            function getMaxWidth(a){
                return ($this.mod('usertree').isAvatar(a))?"max-width:200px;line-height: 35px;padding-left: 5px;":"";
            }
            function getPadding(a){
                return ($this.mod('usertree').isAvatar(a))?"padding:5px;":"padding:10px;";
            }
        }
    }

    $this.init = function(){
        //table user rows
        $users = $fn.getUserArray($this.mod('usertree').getUsers());
        $fn.render();

        //sort header columns
        $($box).find('[familytreetop="sort"]').click(function(){
            var type = $(this).attr('familytreetop-type');
            $fn.order(type);
            $fn.render(true);
            return false;
        });

        //filter checkbox
        $fn.createFilterList();
        $fn.setButtonSize();

        //input search
        var find = function(){
            var temp = $('input.input-medium.search-query').val();
            if(temp.length == 0){
                $('#membersTable tbody tr').show();
            } else {
                $('#membersTable tbody td:nth-child(2):not(:contains("'+temp+'"))').parent().hide();
            }
        }

        $($filter).find('[class-familytreetop="module-padding"] input').click(function(){
            $sort = {};
            $($filter).find('[class-familytreetop="module-padding"] input:checked').each(function(i,e){
                var type = $(e).parent().parent().attr('familytreetop');
                switch(type){
                    case "immediate_family":
                        $sort[1] = true;
                        $sort[2] = true;
                        $sort[3] = true;
                        $sort[4] = true;
                        $sort[5] = true;
                        $sort[6] = true;
                        $sort[7] = true;
                        $sort[8] = true;
                        $sort[10] = true;
                        $sort[11] = true;
                        $sort[12] = true;
                        $sort[13] = true;
                        $sort[110] = true;
                        $sort[111] = true;
                        $sort[112] = true;
                        $sort[113] = true;
                        $sort[210] = true;
                        $sort[211] = true;
                        $sort[212] = true;
                        $sort[213] = true;
                    break;
                    case "grandparents":  $sort[103] = true; $sort[104] = true; $sort[203] = true; $sort[204] = true; break;
                    case "grandchildren": $sort[105] = true; $sort[106] = true; $sort[205] = true; $sort[206] = true; break;
                    case "cousins": $sort[9] = true; break;
                    case "in_laws": $sort[1000] = true; break;
                    case "unknown": $sort["unknown"] = true;
                }
            });
            $fn.render(true);
        });

        $($filter).find('.btn').click(function(){
            if($(this).hasClass('disabled')) return false;
            $(this).parent().find('.btn-success').removeClass('btn-success').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-success');

            var type = $(this).attr('familytreetop').split(':');
            switch(type[0]){
                case "gender":
                    if(type[1] == "both"){
                        $isGender = true;
                    } else {
                        $isGender = {};
                        $isGender['gender'] = (type[1]=="male")?1:0;
                    }
                    break;
                case "living":
                    if(type[1] == "both"){
                        $isLiving = true;
                    } else {
                        $isLiving = {};
                        $isLiving['alive'] = (type[1]=="yes");
                    }
                    break;

                case "registered":
                    if(type[1] == "both"){
                        $isRegistered = true;
                    } else {
                        $isRegistered = {};
                        $isRegistered['registered'] = (type[1]=="yes");
                    }
                    break;

                case "members":
                    if(type[1] == "both"){
                        $isMembers = true;
                    } else {
                        $isMembers = {};
                        $isMembers['ancestors'] = (type[1]=="ancestors");
                    }
                    break;
            }
            $fn.render(true);
        });

        $this.mod('usertree').trigger({}, $fn.render);

        $('html').keyup(function(e){if(e.keyCode == 8)find()});
        $('input.input-medium.search-query').keypress(find);
    }
});