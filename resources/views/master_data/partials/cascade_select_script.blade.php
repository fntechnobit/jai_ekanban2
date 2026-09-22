<script>
    // Helpers for the cascading Area -> Conveyor -> Type -> Machine selects.
    // Options are rebuilt in place and only select2's display is refreshed
    // ('change.select2'), so the pages' own 'change' handlers are not re-fired.
    window.MasterCascade = {
        fillConveyors: function ($select, conveyors, areaId, placeholder) {
            $select.empty().append(new Option(placeholder, ''));
            conveyors
                .filter(function (c) { return !areaId || String(c.area_id) === String(areaId); })
                .forEach(function (c) { $select.append(new Option(c.text, c.id)); });
            $select.val('').trigger('change.select2');
        },

        loadMachines: function ($select, url, params, placeholder) {
            // Ignore responses superseded by a newer request on the same select.
            var token = {};
            $select.data('cascadeRequest', token);
            $select.empty().append(new Option(placeholder, '')).val('').trigger('change.select2');
            return $.get(url, params).done(function (res) {
                if ($select.data('cascadeRequest') !== token) return;
                (res.data || []).forEach(function (m) { $select.append(new Option(m, m)); });
                $select.trigger('change.select2');
            });
        }
    };
</script>
