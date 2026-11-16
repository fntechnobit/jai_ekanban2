<script>
    // Import modal: the Conveyor list only offers conveyors of the chosen Area,
    // and stays disabled until an Area is chosen. A conveyor's area is what the
    // imported rows end up in, so the two must never disagree.
    window.ImportAreaConveyor = {
        /**
         * $area / $conveyor  the modal's jQuery selects. Conveyor <option>s must
         * carry data-area-id (empty when the conveyor has no area yet).
         * Returns { reset } to clear both back to the initial state.
         */
        init: function ($area, $conveyor) {
            var all = $conveyor.find('option').filter(function () {
                return this.value !== '';
            }).map(function () {
                return { id: this.value, text: $(this).text(), area: String($(this).data('area-id') || '') };
            }).get();

            function render() {
                var areaId = String($area.val() || '');
                $conveyor.empty().append(new Option('- Choose Conveyor -', ''));
                all.forEach(function (c) {
                    if (areaId && c.area === areaId) {
                        $conveyor.append(new Option(c.text, c.id));
                    }
                });
                $conveyor.prop('disabled', !areaId).val('').trigger('change.select2');
            }

            $area.on('change', render);
            render();

            return {
                reset: function () {
                    $area.val('').trigger('change');
                }
            };
        }
    };
</script>
