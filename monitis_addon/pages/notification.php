<?php
$oWHMCS = new WHMCS_class(MONITIS_CLIENT_ID);
$monitiAdminList1 = MonitisApi::getContacts();
 

$action = monitisPost('action');
if ($action) {

    switch ($action) {
        case 'activate':
            $id = isset($_POST["id"]) ? $_POST["id"] : '';
            $account = isset($_POST["email"]) ? $_POST["email"] : '';

            if ($id != '') {
                $adminList_test = $oWHMCS->adminList_test($id);
                $contact = json_decode($adminList_test, true);

                foreach ($contact as $arr) {

                    MonitisApi::addContactToGroup($arr['firstname'], $arr['lastname'], $account);
                }
            }
            break;
        case 'deactivate':
            $account = isset($_POST["email"]) ? $_POST["email"] : '';
            if ($account != '') {
                foreach ($monitiAdminList1 as $data) {
                    if ($data['contactAccount'] === $account) {
                        $contactId = $data['contactId'];
                        $contactType = $data['contactType'];

                        MonitisApi::deleteContact($contactId, $account, $contactType);
                    }
                }
            }
            break;
    }
}


$monitiAdminList = MonitisApi::getContacts();
$adminList = $oWHMCS->adminList();

for ($i = 0; $i < count($adminList); $i++) {
    for ($j = 0; $j < count($monitiAdminList); $j++) {

        if ($adminList[$i]['email'] === $monitiAdminList[$j]['contactAccount']) {

            $adminList[$i]['deactive'] = 'deacitve';
        }
    }
}

?>

<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <tr><th>Username</th><th>Email</th><th></th></tr>

    <?php for ($i = 0; $i < count($adminList); $i++) { ?> 
        <?php if (!$adminList[$i]['deactive']) { ?>
            <form method="post" action="" >
                <tr>
                    <td>
                        <input type="text" value="<?= $adminList[$i]['username']; ?>" name="username"  style="background:transparent; border:none;"  readonly/>
                    </td>
                    <td>  
                        <input type="text" value="<?= $adminList[$i]['email']; ?>" name="email" size="70" style="background:transparent; border:none;" readonly/>
                        <input type="hidden" name="id" value="<?= $adminList[$i]['id']; ?>" />
                    </td> 

                    <td>
                        <input type="hidden" name="action" value="activate" />
                        <input type="submit" value="Activate" onclick="this.form.action.value = 'activate';" class="btn-success"  />
                    </td>

                </tr>
            </form>
        <?php } else { ?>
            <form method="post" action="">
                <tr>
                    <td>
                        <input type="text" value="<?= $adminList[$i]['username']; ?>" name="username"  style="background:transparent; border:none;" readonly/>
                    </td>
                    <td>  
                        <input type="text" value="<?= $adminList[$i]['email']; ?>" name="email" size="70" style="background:transparent; border:none;" readonly/>
                        <input type="hidden" name="id" value="<?= $adminList[$i]['id']; ?>" />
                    </td> 

                    <td class="center">
                        <input type="hidden" name="action" value="deactivate" />
                        <input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate';" class="btn-danger"  />
                    </td>

                </tr>
            </form>
        <?php } ?>   
    <?php } ?>
</table>
