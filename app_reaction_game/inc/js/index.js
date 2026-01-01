let cubeInterval = null;

// Checkboxen speichern & wiederherstellen
function saveCubeSelection() {
    let selected = [];
    $('input[name="active_cubes[]"]:checked').each(function() {
        selected.push(this.value);
    });
    return selected;
}

function restoreCubeSelection(selected) {
    $('input[name="active_cubes[]"]').each(function() {
        this.checked = selected.includes(this.value);
    });
}

function loadPositions(excId) {
    $.get('', { ajax: 'get_positions', exc_id: excId }, function(data) {
        if (!data) return;

        let container = $('#positions_container');
        container.empty();

        // Keys sortieren nach created_on
        let keys = Object.keys(data).sort(function(a, b) {
            return new Date(data[a].created_on) - new Date(data[b].created_on);
        });

        keys.forEach(function(key) {
            let pos = data[key];

            let box = $('<div>')
                .addClass('position_box')
                .attr('data-pos-id', pos.pos_id);

            // Sensoren anzeigen: hier vereinfacht, direkt pos_desc = rep_pos_id
            let sensorDiv = $('<div>').addClass('sensors')
                .text('Position ' + pos.pos_id + ': ' + pos.pos_desc);

            box.append(sensorDiv);

            // Aktionen: ↑ ↓ und löschen
            let actions = $('<div>').addClass('actions');
            $('<button>').attr('type','button').addClass('up').text('↑').appendTo(actions);
            $('<button>').attr('type','button').addClass('down').text('↓').appendTo(actions);
            $('<button>').attr('type','button').addClass('del').text('✖').appendTo(actions);

            box.append(actions);
            container.append(box);
        });
    }, 'json');
}



$(function(){

    // Reaction-exercises Liste laden
    function loadList() {
        $.get('', { ajax:'get_reaction_exercises' }, function(data){
            let container = $('#reaction_list');
            container.empty();

            data.forEach(exc => {
                let box = $('<div>').addClass('exercise_box').attr('data-id', exc.re_id);

                $('<div>').addClass('exercise_title').text(exc.re_repetitions + "x " + exc.re_title).appendTo(box);
                $('<div>').addClass('exercise_desc').text(exc.re_description || '').appendTo(box);

                let actions = $('<div>').addClass('exercise_actions');
                $('<button>')
                    .addClass('edit orange')
                    .text('✎ Edit')
                    .attr('data-id', exc.re_id)
                    .appendTo(actions);

                $('<button>')
                    .addClass('delete red')
                    .text('✖ Delete')
                    .attr('data-id', exc.re_id)
                    .appendTo(actions);

                $('<button>')
                    .addClass('start blue')
                    .text('Übung öffnen')
                    .attr('data-id', exc.re_id)
                    .appendTo(actions);
                box.append(actions);

                container.append(box);
            });
        }, 'json');
    }



    loadList();

    // Modal öffnen
    $(document).on('click','.edit,#new_exercise',function(){
        $.get('',{ajax:'get_reaction_exercise_form',id:$(this).data('id')||0},function(h){
            $('#myModalText').html(h);
            $('#myModal').show();
            let id=$('input[name=id]').val();
            if(id>0) loadPositions(id);
        });
    });

    // Modal schließen
    $('.close').click(()=>{ $('#myModal').hide(); });

    // Position hinzufügen per Button (V, M, H)
    $(document).on('click', '.add-pos-btn', function() {
        let value = $(this).data('value'); // V, M oder H
        let excId = $('input[name=id]').val();

        $.get('', { ajax: 'add_position', exc_id: excId, position: value }, function(res) {
            if (res === 'OK') {
                loadPositions(excId);
            } else {
                alert(res);
            }
        });
    });
    // Position löschen
    $(document).on('click', '.del', function() {
        let posId = $(this).closest('.position_box').data('pos-id');
        $.get('', { ajax: 'delete_position', pos_id: posId }, () => loadPositions($('input[name=id]').val()));
    });

    // Cube löschen (vereinfachte Version: gesamte Position)
    $(document).on('click', '.del-cube', function() {
        // Da wir jetzt Sensoren nur als Text anzeigen, entfernen wir einfach die ganze Position
        let box = $(this).closest('.position_box');
        let posId = box.data('pos-id');

        $.get('', { ajax: 'delete_position', pos_id: posId }, () => box.remove());
    });

    // Position hoch/runter
    $(document).on('click', '.up, .down', function() {
        let posId = $(this).closest('.position_box').data('pos-id');
        let dir = $(this).hasClass('up') ? 'up' : 'down';

        $.get('', { ajax: 'move_position', pos_id: posId, dir: dir }, () => loadPositions($('input[name=id]').val()));
    });


    // Reaction-Form speichern
    $(document).on('submit','#edit_reaction_form',function(e){
        e.preventDefault();
        $.get('',$(this).serialize()+'&ajax=save_reaction_exercise',r=>{
            if(r=='OK'){
                $('#myModal').hide();
                loadList();
            }
        });
    });

    $(document).on('click', '.exercise_box .delete', function(){
        let box = $(this).closest('.exercise_box');
        let id = box.data('id');

        $.get('', { ajax: 'delete_exercise', id: id }, function(res){
            if(res === "OK") box.remove();
        });
    });

    $(document).on('click', '.start', function() {
        const excId = $(this).data('id');
        // Hier URL anpassen, z. B. auf eine Detailseite
        window.location.href = 'overview.php?exc_id=' + excId;
    });
});
