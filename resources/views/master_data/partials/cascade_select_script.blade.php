<script>
    // Cascading Area -> Family -> Conveyor -> Type -> Machine selects.
    // Family, Conveyor and Machine options come from `url` (the data itself),
    // narrowed by the levels above. Options are rebuilt in place and only
    // select2's display is refreshed ('change.select2'), so the 'change'
    // handlers below never re-fire from our own updates.
    window.MasterCascade = {
        /**
         * cfg.selects      {area, family, conveyor, type, machine} jQuery selects
         * cfg.placeholders {family, conveyor, machine} empty-option labels
         * cfg.typeParam    request field for the Type level ('process' / 'type')
         * cfg.required     true = a level stays disabled until its parent is chosen
         * cfg.onChange     called after the user changes any level
         */
        create: function (cfg) {
            var s = cfg.selects;
            var parentOf = { family: 'area', conveyor: 'family', machine: 'conveyor' };
            var below = {
                area: ['family', 'conveyor', 'machine'],
                family: ['conveyor', 'machine'],
                conveyor: ['machine'],
                type: ['machine']
            };
            var lists = { family: 'families', conveyor: 'conveyors', machine: 'machines' };
            var pending = null;

            function empty(level) {
                s[level].empty().append(new Option(cfg.placeholders[level], '')).val('').trigger('change.select2');
            }

            function refresh(changed) {
                var levels = below[changed];
                levels.forEach(empty);

                if (cfg.required) {
                    ['family', 'conveyor', 'machine'].forEach(function (level) {
                        s[level].prop('disabled', !s[parentOf[level]].val());
                    });
                }

                // With required parents nothing can be offered until the level above is chosen.
                var loadable = levels.filter(function (level) {
                    return !cfg.required || s[parentOf[level]].val();
                });
                if (!loadable.length) return;

                var params = {
                    area_id: s.area.val(),
                    family: s.family.val(),
                    conveyor_id: s.conveyor.val()
                };
                params[cfg.typeParam] = s.type.val();

                // Ignore responses superseded by a newer request.
                var request = pending = {};
                $.get(cfg.url, params).done(function (res) {
                    if (request !== pending) return;
                    var data = res.data || {};
                    loadable.forEach(function (level) {
                        (data[lists[level]] || []).forEach(function (o) {
                            s[level].append(typeof o === 'object' ? new Option(o.text, o.id) : new Option(o, o));
                        });
                        s[level].trigger('change.select2');
                    });
                });
            }

            Object.keys(below).forEach(function (level) {
                s[level].on('change', function () {
                    refresh(level);
                    if (cfg.onChange) cfg.onChange();
                });
            });
            s.machine.on('change', function () {
                if (cfg.onChange) cfg.onChange();
            });

            var api = {
                // Clear every level and reload the options from the top.
                reset: function () {
                    s.area.val('').trigger('change.select2');
                    s.type.val('').trigger('change.select2');
                    refresh('area');
                }
            };
            api.reset();
            return api;
        }
    };
</script>
