<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <h1><?php echo $title. ' <span class="orange">' . $data->name . '</span>'; ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    
                    <?php if(isset($response)) { printResponse($response); } ?>
                    
                    <form action="/user/edit/<?php echo $data->idusers; ?>" method="post" enctype="multipart/form-data">
                        
                        <input type="hidden" name="id" id="id" value="<?php echo $data->idusers; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                                    <label for="name">User Name:</label>
                                    <input type="text" name="name" id="name" class="form-control" value="<?php echo $data->name; ?>">
                                    <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo '<span class="help-block text-danger">' . $error['name'] . '</span>'; } ?>
                                </div>
                                <div class="form-group <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo "has-error"; } ?>">
                                    <label for="email">Email:</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?php echo $data->email; ?>">
                                    <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo '<span class="help-block text-danger">' . $error['email'] . '</span>'; } ?>
                                </div>
                        
                                
                                <?php if(hasRole($user, 'Administrator')){ ?>
                                <div class="form-group <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo "has-error"; } ?>">
                                    
                                    <label for="role">User Role:</label>
                                    <select id="role" name="role"  class="form-control selectpicker">
                                        <option></option>
                                        <?php foreach($roles as $role){ ?>
                                        <option value="<?php echo $role->id; ?>" <?php echo ($role->id == $data->role ? 'selected' : ''); ?>><?php echo $role->role; ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo '<span class="help-block text-danger">' . $error['role'] . '</span>'; } ?>
                                </div>
                                <?php } ?>
                            </div>    
                            <div class="col-md-6">  
                                
                                <div class="form-group">
                                    <label for="profile">Profile Picture:</label>
                                    <input type="file" class="form-control file" name="profile" data-show-upload="false" data-show-caption="true">
                                </div>
                                
                                
                                <?php $groupclass = (hasRole($user, 'Administrator') ? 'show' : 'hidden'); ?>
                                <div class="form-group <?php echo $groupclass; ?> <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo "has-error"; } ?>">
                                    <label for="group">Group(s):</label>
                                    
                                        <?php foreach($groups as $group){ ?>
                                         <div class="checkbox">
                                            <label>
                                                <input
                                                    value="<?php echo $group->id; ?>"
                                                    type="checkbox"
                                                    name="groups[]"
                                                    id="group-<?php echo $group->id; ?>"
                                                    <?php echo (in_array($group->id, $data->groups) ? ' checked ' : ''); ?>
                                                >    
                                                <?php echo $group->name; ?>
                                            </label>
                                        </div>
                                        <?php } ?>
                                    <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo '<span class="help-block text-danger">' . $error['group'] . '</span>'; } ?>
                                </div>
                                
                                
                                <div class="form-group">
                                    <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>                                    
                                </div>   
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>