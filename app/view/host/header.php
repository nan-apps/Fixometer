<div class="container">
    <div class="row host-header">
        
        <div class="col-md-1">
            <?php $home_url = (hasRole($user, 'Administrator') ? '/admin' : '/host'); ?>
            <a href="<?php echo $home_url; ?>">
                <img class="img-responsive" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
            </a>
        </div>
        
        <div class="col-md-11 text-center">
            
            <span class="header-text">
            Welcome to the Fixometer, <strong><?php echo $user->name; ?></strong>. 
            
            <?php if ($showbadges) { ?> 
                <span class="">You have <a class="label label-success" href="#upcomingparties"><?php echo count($upcomingparties); ?></a> upcoming <?php echo (count($upcomingparties) == 1 ? 'party' : 'parties'); ?>.
                <?php if ($need_attention > 0) { ?>
                    <a class="label label-danger" href="#attention"><?php echo $need_attention; ?></a> <?php echo ($need_attention == 1 ? 'party needs' : 'parties need'); ?> your attention.</span>
                <?php } ?>
            <?php  } ?>
            </span>
            
            <div class="dropdown pull-right profile-button">
                <button class="btn btn-default dropdown-toggle" type="button" id="profileDropDown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?php if(empty($profile->path)){ ?>
                    <img src="http://www.placehold.it/40?text=you" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } else { ?>
                    <img src="/uploads/thumbnail_<?php echo $profile->path; ?>" width="40" height="40" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } ?>
                    <i class="fa fa-caret-down"></i>
                </button>
                
                 <ul class="dropdown-menu" aria-labelledby="profileDropDown">
                    <li><a href="/host"><i class="fa fa-dashboard"></i> Host Dashboard</a></li>
                    <li><a href="/user/edit/<?php echo $profile->idusers; ?>"><i class="fa fa-edit"></i> Edit Profile</a></li>
                    <li><a class="" href="/user/logout"><i class="fa fa-sign-out"></i> Logout</a></li>
                  </ul>
                
            </div>
        </div>
        <!-- 
        <div class="col-md-1 col-md-offset-2">
            
            
            
            
        </div>
        -->
    </div>
    
</div>

<?php /*
       *  <div class="col-md-6">
            <strong>My Profile</strong> <a href="/user/edit/<?php echo $profile->idusers; ?>" class="small"><i class="fa fa-edit"></i> Edit Profile...</a>
            <div class="media">
                <div class="media-left">
                    <?php if(empty($profile->path)){ ?>
                    <img src="http://www.lorempixum.com/80/80/people" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } else { ?>
                    <img src="/uploads/<?php echo $profile->path; ?>" width="80" height="80" alt="<?php echo $profile->name; ?> Image" class="profile-pic" />
                    <?php } ?>
                </div>
                <div class="media-body">
                    <h3 class="media-heading"><?php echo $profile->name; ?></h3>
                </div>
            </div>
        </div>
        */
?>