<?php
// $HeadURL: https://joomgallery.org/svn/joomgallery/JG-3/Addons/MigrateMVC2JUX/trunk/migratemvc2jux.php $
// $Id: migratemvc2jux.php 4289 2013-05-28 14:43:12Z chraneco $
/******************************************************************************\
**   JoomGallery Migration Script MVC2JUX 3.0                                 **
**   By: JoomGallery::ProjectTeam                                             **
**   Copyright (C) 2011 JoomGallery::ProjectTeam                              **
**   Released under GNU GPL Public License                                    **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look             **
**   at administrator/components/com_joomgallery/LICENSE.TXT                  **
\******************************************************************************/

/******************************************************************************\
**   Migration of DB and Files from JoomGallery 1.5.7 to Joomgallery 3 JUX    **
**   On the fly generation of categories in db and file system                **
**   moving the images into the new categories                                **
\******************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Migration script class
 *
 * @package JoomGallery
 * @since   1.0
 */
class JoomMigrateMvc2Jux extends JoomMigration
{
  /**
   * The name of the migration
   * (should be unique)
   *
   * @var string
   */
  protected $migration = 'mvc2jux';

  /**
   * Properties for paths and database table names of old JoomGallery to migrate from
   *
   * @var string
   */
  protected $prefix;
  protected $path;
  protected $path_originals;
  protected $path_details;
  protected $path_thumbnails;
  protected $table_images;
  protected $table_categories;
  protected $table_comments;
  protected $table_config;
  protected $table_nameshields;
  protected $table_users;
  protected $table_votes;

  /**
   * Constructor
   *
   * @return  void
   * @since   1.0
   */
  public function __construct()
  {
    parent::__construct();

    // Create the image paths and table names
    $this->prefix = $this->getStateFromRequest('prefix', 'prefix', '', 'cmd');
    $this->path   = $this->getStateFromRequest('path2joomla', 'path2joomla', '', 'string');
    if($this->path == '-')
    {
      $this->path_originals   = $this->getStateFromRequest('originals', 'originals', '', 'string');
      $this->path_details     = $this->getStateFromRequest('details', 'details', '', 'string');
      $this->path_thumbnails  = $this->getStateFromRequest('thumbnails', 'thumbnails', '', 'string');
    }
    else
    {
      $this->path             = JPath::clean(rtrim($this->path, '\/'));
      $this->path_originals   = $this->path.'/'.$this->getStateFromRequest('originals', 'originals', '', 'string');
      $this->path_details     = $this->path.'/'.$this->getStateFromRequest('details', 'details', '', 'string');
      $this->path_thumbnails  = $this->path.'/'.$this->getStateFromRequest('thumbnails', 'thumbnails', '', 'string');
    }

    $this->path_originals     = JPath::clean(rtrim($this->path_originals, '\/').'/');
    $this->path_details       = JPath::clean(rtrim($this->path_details, '\/').'/');
    $this->path_thumbnails    = JPath::clean(rtrim($this->path_thumbnails, '\/').'/');

    $this->table_images       = str_replace('#__', $this->prefix, _JOOM_TABLE_IMAGES);
    $this->table_categories   = str_replace('#__', $this->prefix, _JOOM_TABLE_CATEGORIES);
    $this->table_comments     = str_replace('#__', $this->prefix, _JOOM_TABLE_COMMENTS);
    $this->table_config       = str_replace('#__', $this->prefix, _JOOM_TABLE_CONFIG);
    $this->table_nameshields  = str_replace('#__', $this->prefix, _JOOM_TABLE_NAMESHIELDS);
    $this->table_users        = str_replace('#__', $this->prefix, _JOOM_TABLE_USERS);
    $this->table_votes        = str_replace('#__', $this->prefix, _JOOM_TABLE_VOTES);
  }

  /**
   * Checks requirements for migration
   *
   * @return  void
   * @since   1.0
   */
  public function check($dirs = array(), $tables = array(), $xml = false, $min_version = false, $max_version = false)
  {
    if($this->path == JPATH_ROOT)
    {
      JFactory::getLanguage()->load('com_joomgallery.migratemvc2jux');
      $this->_mainframe->redirect('index.php?option='._JOOM_OPTION.'&controller=migration', JText::_('FILES_JOOMGALLERY_MIGRATION_MVC2JUX_WRONG_PATH2JOOMLA'), 'notice');
    }

    if(!$this->otherDatabase && $this->prefix == $this->_db->getPrefix())
    {
      JFactory::getLanguage()->load('com_joomgallery.migratemvc2jux');
      $this->_mainframe->redirect('index.php?option='._JOOM_OPTION.'&controller=migration', JText::_('FILES_JOOMGALLERY_MIGRATION_MVC2JUX_WRONG_PREFIX'), 'notice');
    }

    $dirs         = array($this->path_originals,
                          $this->path_details,
                          $this->path_thumbnails);
    $tables       = array($this->table_images,
                          $this->table_categories,
                          $this->table_comments,
                          $this->table_nameshields,
                          $this->table_users,
                          $this->table_votes);
    $xml = null;
    if($this->path != '-')
    {
      $xml  = JPath::clean($this->path.'/administrator/components/'._JOOM_OPTION.'/joomgallery.xml');
    }

    $min_version  = '1.5.7.5';
    $max_version  = '1.5.7.5';

    parent::check($dirs, $tables, $xml, $min_version, $max_version);
  }

  /**
   * Main migration function
   *
   * @return  void
   * @since   1.0
   */
  protected function doMigration()
  {
    $task = $this->getTask('categories');

    switch($task)
    {
      case 'categories':
        $this->migrateCategories();
        // Break intentionally omited
      case 'rebuild':
        $this->rebuild();
        // Break intentionally omited
      case 'images':
        $this->migrateImages();
        // Break intentionally omited
      case 'comments':
        $this->migrateComments();
        // Break intentionally omited
      case 'nametags':
        $this->migrateNametags();
        // Break intentionally omited
      case 'users':
        $this->migrateUsers();
        // Break intentionally omited
      case 'votes':
        $this->migrateVotes();
        // Break intentionally omited
      case 'config':
        $this->migrateConfig();
        // Break intentionally omited
      default:
        break;
    }
  }

  /**
   * Returns the maximum category ID of JoomGallery 1.5.7.
   *
   * @return  int   The maximum category ID of JoomGallery 1.5.7
   * @since   1.0
   */
  protected function getMaxCategoryId()
  {
    $query = $this->_db2->getQuery(true)
          ->select('MAX(cid)')
          ->from($this->table_categories);
    $this->_db2->setQuery($query);

    return $this->runQuery('loadResult', $this->_db2);
  }

  /**
   * Migrates all categories
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateCategories()
  {
    $query = $this->_db->getQuery(true)
          ->select('*')
          ->from($this->table_categories);
    $this->prepareTable($query, $this->table_categories, 'parent', array(0));

    while($cat = $this->getNextObject())
    {
      // Make information accessible for JoomGallery
      $cat->parent_id = $cat->parent;
      $cat->access    = $cat->access + 1;

      // Search for thumbnail
      $cat->thumbnail = 0;
      if($cat->catimage)
      {
        $search_query = $this->_db2->getQuery(true)
                      ->select('id')
                      ->from($this->table_images)
                      ->where('imgthumbname = '.$this->_db2->quote($cat->catimage));
        $this->_db2->setQuery($search_query);
        $cat->thumbnail = $this->runQuery('loadResult', $this->_db2);
      }

      $this->createCategory($cat);

      $this->markAsMigrated($cat->cid, 'cid', $this->table_categories);

      if(!$this->checkTime())
      {
        $this->refresh();
      }
    }

    $this->resetTable($this->table_categories);
  }

  /**
   * Migrates all images
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateImages()
  {
    $query = $this->_db->getQuery(true)
          ->select('i.*, c.catpath')
          ->from($this->table_images.' AS i')
          ->leftJoin($this->table_categories.' AS c ON i.catid = c.cid');
    $this->prepareTable($query);

    while($row = $this->getNextObject())
    {
      $original   = $this->path_originals.$row->catpath.'/'.$row->imgfilename;
      $detail     = $this->path_details.$row->catpath.'/'.$row->imgfilename;
      $thumbnail  = $this->path_thumbnails.$row->catpath.'/'.$row->imgthumbname;

      $no_original = false;
      if(!JFile::exists($original))
      {
        $no_original  = true;
        $original = $detail;
        $detail   = null;
      }

      $this->moveAndResizeImage($row, $original, $detail, $thumbnail, false);

      if($no_original)
      {
        // If there was no original image in the old gallery move the
        // original image in new gallery into detail images directory
        $original = $this->_ambit->getImg('orig_path', $row);
        $detail   = $this->_ambit->getImg('img_path', $row);
        if(!JFile::move($original, $detail))
        {
          $this->setError('Error moving '.$original.' to '.$detail);
        }
      }

      if(!$this->checkTime())
      {
        $this->refresh('images');
      }
    }

    $this->resetTable();
  }

  /**
   * Migrates all comments
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateComments()
  {
    $selectQuery = $this->_db2->getQuery(true)
                ->select('a.*')
                ->from($this->table_comments.' AS a');

    if(!$this->otherDatabase)
    {
      if(!$this->checkTime())
      {
        $this->refresh('comments');
      }

      $this->writeLogfile('Start migrating comments');
      if($this->checkOwner)
      {
        $selectQuery->leftJoin('#__users AS u ON a.userid = u.id')
                    ->where('a.userid = 0 OR u.id IS NOT NULL');
      }
      $query = 'INSERT INTO '._JOOM_TABLE_COMMENTS.' '.$selectQuery;
      $this->_db->setQuery($query);
      if($this->runQuery())
      {
        $this->writeLogfile('Comments successfully migrated');
      }
      else
      {
        $this->writeLogfile('Error migrating the commments');
      }

      return;
    }

    $this->prepareTable($selectQuery);

    while($row = $this->getNextObject())
    {
      if(!$this->checkOwner || JUser::getTable()->load($row->userid))
      {
        $this->createComment($row);
      }

      if(!$this->checkTime())
      {
        $this->refresh('comments');
      }
    }

    $this->resetTable();
  }

  /**
   * Migrates all name tags
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateNametags()
  {
    $selectQuery = $this->_db2->getQuery(true)
                ->select('a.*')
                ->from($this->table_nameshields.' AS a');

    if(!$this->otherDatabase)
    {
      if(!$this->checkTime())
      {
        $this->refresh('nametags');
      }

      $this->writeLogfile('Start migrating name tags');
      if($this->checkOwner)
      {
        $selectQuery->leftJoin('#__users AS u ON a.nuserid = u.id')
                    ->where('u.id IS NOT NULL');
      }
      $query = 'INSERT INTO '._JOOM_TABLE_NAMESHIELDS.' '.$selectQuery;
      $this->_db->setQuery($query);
      if($this->runQuery())
      {
        $this->writeLogfile('Name tags successfully migrated');
      }
      else
      {
        $this->writeLogfile('Error migrating the name tags');
      }

      return;
    }

    $this->prepareTable($selectQuery);

    while($row = $this->getNextObject())
    {
      if(!$this->checkOwner || JUser::getTable()->load($row->nuserid))
      {
        $this->createNametag($row);
      }

      if(!$this->checkTime())
      {
        $this->refresh('nametags');
      }
    }

    $this->resetTable();
  }

  /**
   * Migrates all users
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateUsers()
  {
    $selectQuery = $this->_db2->getQuery(true)
                ->select('a.*')
                ->from($this->table_users.' AS a')
                ->where('uuserid != 0');

    if(!$this->otherDatabase)
    {
      if(!$this->checkTime())
      {
        $this->refresh('users');
      }

      $this->writeLogfile('Start migrating users');
      if($this->checkOwner)
      {
        $selectQuery->leftJoin('#__users AS u ON a.uuserid = u.id')
                    ->where('u.id IS NOT NULL');
      }
      $query = 'INSERT INTO '._JOOM_TABLE_USERS.' '.$selectQuery;
      $this->_db->setQuery($query);
      if($this->runQuery())
      {
        $this->writeLogfile('Users successfully migrated');
      }
      else
      {
        $this->writeLogfile('Error migrating the users');
      }

      return;
    }

    $this->prepareTable($selectQuery);

    while($row = $this->getNextObject())
    {
      if(!$this->checkOwner || JUser::getTable()->load($row->uuserid))
      {
        $this->createUser($row);
      }

      if(!$this->checkTime())
      {
        $this->refresh('users');
      }
    }

    $this->resetTable();
  }

  /**
   * Migrates all votes
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateVotes()
  {
    $selectQuery = $this->_db2->getQuery(true)
                ->select('a.*')
                ->from($this->table_votes.' AS a');

    if(!$this->otherDatabase)
    {
      if(!$this->checkTime())
      {
        $this->refresh('votes');
      }

      $this->writeLogfile('Start migrating votes');
      if($this->checkOwner)
      {
        $selectQuery->leftJoin('#__users AS u ON a.userid = u.id')
                    ->where('u.id IS NOT NULL');
      }
      $query = 'INSERT INTO '._JOOM_TABLE_VOTES.' '.$selectQuery;
      $this->_db->setQuery($query);
      if($this->runQuery())
      {
        $this->writeLogfile('Votes successfully migrated');
      }
      else
      {
        $this->writeLogfile('Error migrating the votes');
      }

      return;
    }

    $this->prepareTable($selectQuery);

    while($row = $this->getNextObject())
    {
      if(!$this->checkOwner || JUser::getTable()->load($row->userid))
      {
        $this->createVote($row);
      }

      if(!$this->checkTime())
      {
        $this->refresh('votes');
      }
    }

    $this->resetTable();
  }

  /**
   * Migrates configuration settings (where possible)
   *
   * @return  void
   * @since   1.0
   */
  protected function migrateConfig()
  {
    if(!$this->checkTime())
    {
      $this->refresh('config');
    }

    $migrateable_settings = array('jg_use_real_paths', 'jg_dateformat', 'jg_checkupdate',
                                  'jg_thumbcreation', 'jg_fastgd2thumbcreation', 'jg_impath', 'jg_resizetomaxwidth', 'jg_maxwidth', 'jg_picturequality', 'jg_useforresizedirection', 'jg_cropposition', 'jg_thumbwidth', 'jg_thumbheight', 'jg_thumbquality',
                                  'jg_uploadorder', 'jg_useorigfilename', 'jg_filenamenumber', 'jg_delete_original', 'jg_wrongvaluecolor',
                                  'jg_msg_upload_type', 'jg_msg_upload_recipients', 'jg_msg_download_type', 'jg_msg_zipdownload', 'jg_msg_download_recipients', 'jg_msg_comment_type', 'jg_msg_comment_recipients', 'jg_msg_comment_toowner', 'jg_msg_nametag_type', 'jg_msg_nametag_recipients', 'jg_msg_nametag_totaggeduser', 'jg_msg_nametag_toowner', 'jg_msg_report_type', 'jg_msg_report_recipients', 'jg_msg_report_toowner',
                                  'jg_realname', 'jg_cooliris', 'jg_coolirislink', 'jg_contentpluginsenabled', 'jg_itemid',
                                  'jg_userspace', 'jg_approve', 'jg_maxusercat', 'jg_maxuserimage', 'jg_maxfilesize', 'jg_usercatacc', 'jg_maxuploadfields', 'jg_useruploadsingle', 'jg_useruploadbatch', 'jg_useruploadjava', 'jg_useruseorigfilename', 'jg_useruploadnumber', 'jg_special_gif_upload', 'jg_delete_original_user', 'jg_newpiccopyright', 'jg_newpicnote', 'jg_redirect_after_upload',
                                  'jg_downloadfile', 'jg_downloadwithwatermark',
                                  'jg_showrating', 'jg_maxvoting', 'jg_ratingcalctype', 'jg_ratingdisplaytype', 'jg_ajaxrating', 'jg_onlyreguservotes AS jg_votingonlyonce',
                                  'jg_showcomment', 'jg_anoncomment', 'jg_namedanoncomment', 'jg_approvecom', 'jg_bbcodesupport', 'jg_smiliesupport', 'jg_anismilie', 'jg_smiliescolor',
                                  'jg_anchors', 'jg_tooltips', 'jg_dyncrop', 'jg_dyncropposition', 'jg_dyncropwidth', 'jg_dyncropheight', 'jg_dyncropbgcol', 'jg_hideemptycats', 'jg_imgalign',
                                  'jg_firstorder', 'jg_secondorder', 'jg_thirdorder',
                                  'jg_showgalleryhead', 'jg_showpathway', 'jg_completebreadcrumbs', 'jg_showallpics', 'jg_showallhits', 'jg_showbacklink', 'jg_suppresscredits', 'jg_showrmsmcats AS jg_showrestrictedcats', 'jg_rmsm AS jg_showrestrictedhint',
                                  'jg_showallpicstoadmin', 'jg_showminithumbs', 'jg_openjs_padding', 'jg_openjs_background', 'jg_dhtml_border', 'jg_show_title_in_dhtml AS jg_show_title_in_popup', 'jg_show_description_in_dhtml AS jg_show_description_in_popup', 'jg_lightbox_speed', 'jg_lightbox_slide_all', 'jg_resize_js_image', 'jg_disable_rightclick_original',
                                  'jg_showgallerysubhead', 'jg_showallcathead', 'jg_colcat', 'jg_catperpage', 'jg_ordercatbyalpha', 'jg_showgallerypagenav', 'jg_showcatcount', 'jg_showcatthumb', 'jg_showrandomcatthumb', 'jg_ctalign', 'jg_showtotalcatimages', 'jg_showtotalcathits', 'jg_showcatasnew', 'jg_catdaysnew', 'jg_showdescriptioningalleryview', 'jg_showsubsingalleryview',
                                  'jg_category_rss', 'jg_showcathead', 'jg_usercatorder', 'jg_usercatorderlist', 'jg_showcatdescriptionincat', 'jg_showpagenav', 'jg_showpiccount', 'jg_perpage', 'jg_catthumbalign', 'jg_colnumb', 'jg_detailpic_open', 'jg_lightboxbigpic', 'jg_showtitle', 'jg_showpicasnew', 'jg_daysnew', 'jg_showhits', 'jg_showauthor', 'jg_showowner', 'jg_showcatcom', 'jg_showcatrate', 'jg_showcatdescription', 'jg_showcategoryfavourite', 'jg_showcategoryeditorlinks',
                                  'jg_showsubcathead', 'jg_showsubcatcount', 'jg_colsubcat', 'jg_subperpage', 'jg_showpagenavsubs', 'jg_subcatthumbalign', 'jg_showsubthumbs', 'jg_showrandomsubthumb', 'jg_showdescriptionincategoryview', 'jg_ordersubcatbyalpha', 'jg_showtotalsubcatimages', 'jg_showtotalsubcathits',
                                  'jg_showdetailpage', 'jg_disabledetailpage', 'jg_showdetailnumberofpics', 'jg_cursor_navigation', 'jg_disable_rightclick_detail', 'jg_showdetaileditorlinks', 'jg_showdetailtitle', 'jg_showdetail', 'jg_showdetailaccordion', 'jg_showdetaildescription', 'jg_showdetaildatum', 'jg_showdetailhits', 'jg_showdetailrating', 'jg_showdetailfilesize', 'jg_showdetailauthor', 'jg_showoriginalfilesize', 'jg_watermark', 'jg_watermarkpos', 'jg_bigpic', 'jg_bigpic_open', 'jg_bbcodelink', 'jg_showcommentsunreg', 'jg_showcommentsarea', 'jg_send2friend',
                                  'jg_minis', 'jg_motionminis', 'jg_motionminiWidth', 'jg_motionminiHeight', 'jg_miniWidth', 'jg_miniHeight', 'jg_minisprop',
                                  'jg_nameshields', 'jg_nameshields_others', 'jg_nameshields_unreg', 'jg_show_nameshields_unreg', 'jg_nameshields_height', 'jg_nameshields_width',
                                  'jg_slideshow', 'jg_slideshow_timer', 'jg_slideshow_transition', 'jg_slideshow_transtime', 'jg_slideshow_maxdimauto', 'jg_slideshow_width', 'jg_slideshow_heigth', 'jg_slideshow_infopane', 'jg_slideshow_carousel', 'jg_slideshow_arrows', 'jg_slideshow_repeat',
                                  'jg_showexifdata', 'jg_geotagging', 'jg_subifdtags', 'jg_ifdotags', 'jg_gpstags',
                                  'jg_showiptcdata', 'jg_iptctags',
                                  'jg_showtoplist', 'jg_toplist', 'jg_topthumbalign', 'jg_toptextalign', 'jg_toplistcols', 'jg_whereshowtoplist', 'jg_showrate', 'jg_showlatest', 'jg_showcom', 'jg_showthiscomment', 'jg_showmostviewed', 'jg_showtoplistfavourite', 'jg_showtoplisteditorlinks',
                                  'jg_favourites', 'jg_favouritesshownotauth', 'jg_maxfavourites', 'jg_zipdownload', 'jg_usefavouritesforpubliczip', 'jg_usefavouritesforzip', 'jg_showfavouriteseditorlinks',
                                  'jg_search', 'jg_searchcols', 'jg_searchthumbalign', 'jg_searchtextalign', 'jg_showsearchfavourite', 'jg_showsearcheditorlinks'
                                  );

    $this->writeLogfile('Start migrating configuration');
    $query = $this->_db2->getQuery(true)
          ->select($migrateable_settings)
          ->from($this->table_config)
          ->where('id = 1');
    $this->_db2->setQuery($query);

    if(!$settings = $this->runQuery('loadObject', $this->_db2))
    {
      $this->setError('Old configuration settings not found');

      return;
    }

    $config = JoomConfig::getInstance('admin');
    if(!$config->save($settings, 1))
    {
      $this->setError('Unable to store migrated settings');

      return;
    }

    $this->writeLogfile('Configuration successfully migrated');

    $query->clear()
          ->select('COUNT(id)')
          ->from(_JOOM_TABLE_CONFIG);
    $this->_db->setQuery($query);
    if($this->runQuery('loadResult') > 1)
    {
      // Propagate global settings to all config rows
      $this->writeLogfile('Propagate migrated settings to all other config rows');
      $model = JModel::getInstance('Configs', 'JoomGalleryModel');
      if(!$model->propagateChanges($settings))
      {
        $this->setError($model->getError());

        return;
      }

      $this->writeLogfile('Settings were successfully propagated');
    }
  }
}