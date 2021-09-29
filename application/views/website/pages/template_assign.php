<div class="row">

    <div class="col-sm-12">
        <div  class="panel panel-default thumbnail">
 
            <div class="panel-heading no-print">
                <div class="btn-group"> 
                    <?php
                    if($this->permission->method('add_template','create')->access() ){
                    ?>
                    <a class="btn btn-success" href="<?php echo base_url("website/template_assign/create") ?>"> <i class="fa fa-plus"></i>  <?php echo display('add_template') ?> </a>  
                    <?php } ?>

                </div>
            </div> 

            <div class="alert"></div>
             <?php
             if($this->permission->method('templte','read')->access()){
             ?>     
            <div class="panel-body"> 
                
               
                <table width="100%" class="datatable table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th><?php echo display('serial') ?></th>
                            <th><?php echo display('title') ?></th>
                            <th><?php echo display('contents') ?></th>
                            <th><?php echo display('menu_name') ?></th>
                            <th><?php echo display('template_name') ?></th>
                            <th class="no-print"><?php echo display('action') ?></th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($templates)) { ?>
                            <?php $sl = 1; ?>
                            <?php foreach ($templates as $template) { ?>
                                <tr>
                                    <td><?php echo $sl; ?></td>
                                    <td><?php echo $template->title; ?></td>
                                    <td><?php echo character_limiter(strip_tags($template->description),50); ?></td>
                                    <td><?php echo $template->name; ?></td>
                                    <td><?php echo $template->url; ?></td> 
                                    <td class="center no-print" width="110"> 
                                        
                                        <a  href="<?php echo base_url('website/template_assign/edit/'.$template->id) ?>" class="btn btn-xs btn-success"><i class="fa fa-edit"></i></a>
                                        <a href="<?php echo base_url("website/template_assign/delete/".$template->id) ?>" class="btn btn-xs btn-danger" onclick="return confirm('<?php echo display('are_you_sure') ?>') "><i class="fa fa-trash"></i></a> 
                                    
                                    </td>
                                </tr>
                                <?php $sl++; ?>
                            <?php } ?> 
                        <?php } ?> 
                    </tbody>
                </table>  <!-- /.table-responsive -->

            </div>

         <?php 
           }
            else{
                ?>
                 <div class="col-sm-12">
                   <div class="panel panel-bd lobidrag">
                    <div class="panel-heading">
                      <div class="panel-title">
                        <h4><?php echo display('you_do_not_have_permission_to_access_please_contact_with_administrator');?>.</h4>
                       </div>
                    </div>
                 </div>
                </div>
             <?php
            }
          ?>

        </div>
    </div>
  
</div>
<script type="text/javascript">
$(document).ready(function() {

    var source = $('input[id^="add_to_website"]');
    var target = $('.alert');
    source.on('change', function() {
        var id     = $(this).attr('data-id');
        var value  = $(this).attr('data-value'); 

        $.ajax({
            url      : '<?= base_url('website/menu/change_status') ?>',
            type     : 'post',
            dataType : 'json',
            data     : {
                '<?= $this->security->get_csrf_token_name(); ?>' : '<?= $this->security->get_csrf_hash(); ?>',
                id, 
                value
            },
            success : function(data) { 
                if (data.message) {
                    target.removeClass('alert-danger');
                    target.addClass('alert-info');
                    target.html(data.message);
                } else {
                    target.removeClass('alert-info');
                    target.addClass('alert-danger');
                    target.html(data.exception);
                } 

                setInterval(function(){ 
                    history.go(0);
                }, 1500);

            },
            error   : function(exc){
                alert('failed');
            }
        });
 

    }); 
});
</script>