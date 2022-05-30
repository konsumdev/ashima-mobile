import { Component, OnInit } from '@angular/core';
import { HoursProvider } from 'src/app/services/hours/hours';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { AlertController, NavController, LoadingController } from '@ionic/angular';
import { formatDate } from '@angular/common';
import { Router } from '@angular/router';

@Component({
  selector: 'app-apply-ot',
  templateUrl: './apply-ot.page.html',
  styleUrls: ['./apply-ot.page.scss'],
})
export class ApplyOtPage implements OnInit {
    minDate: any;
    maxDate: any;

    startDate: any;
    endDate: any;
    reason: any;
    totalHrs: any;
    isPristine: boolean = false;
    okPayroll: boolean = true;
    islocked: boolean = false;

    constructor(
      private hoursProv: HoursProvider,
      public comCtrl: MyCommonServices,
      public alertCtrl: AlertController,
      public nav: NavController,
      public loadingCtrl: LoadingController,
      private router: Router,
    ) { }

    ngOnInit() {
      this.checkLock();
    }

    async lockAlert() {
      const alert = await this.alertCtrl.create({
          header: 'Locked!',
          message: "Filing of overtime is currently suspended by your admin.  You may try again at a different time.",
          buttons: [
              {
                  text: 'Got it!',
                  role: 'cancel',
                  handler: () => {
                    this.nav.navigateBack('/members/overtime');
                  }
              }
          ]
      });
      await alert.present();
    }

    checkLock() {
        this.comCtrl.presentLoadingDefault();
        this.hoursProv.checkLocks().then(res=>{
            
            if (res) {
                let rslt: any = res;
                console.log(rslt.error);
                if (!rslt.error) {
                    if (rslt.lock_this_timesheet || rslt.recal_timesheet || rslt.recal_payroll) {
                        this.islocked = true;
                        this.lockAlert();
                    }
                }
            }

            this.loadingCtrl.dismiss();
        }).catch(err=>{
            console.log(err);
        });
    }

    async presentLoading() {
        const loading = await this.loadingCtrl.create({
          message: 'Please wait...',
          duration: 10000
        });
        await loading.present();
    
        const { role, data } = await loading.onDidDismiss();
    
        console.log('Loading dismissed!');
     }
    

    submitOt() {
        let asd = this.getLogs();

        let errs = this.hoursProv.overtimeLogValidation(asd);
        if (errs) {
            this.comCtrl.presentLogsError(errs);
            return false;
        }

        console.log("submitting");

        this.presentLoading();

        this.hoursProv.submitOvertime(asd).then(res=>{
            console.log("inside fxn");
            let rslt: any = res;
            if (res) {
                if (rslt.error) {
                    this.comCtrl.presentToast(rslt.msg, "error");
                } else {                    
                    this.comCtrl.presentToast(rslt.msg, 'success');
                    this.nav.navigateBack('/members/overtime');
                    this.loadingCtrl.dismiss();
                }
            }
        }).catch(err=>{
            console.log(err);
        });

    }

    ifPayrollLock() {

        let now = new Date(this.startDate);
        let logDate = formatDate(now, 'yyyy-MM-dd', 'en-US');
        this.hoursProv.checkPayrollLockOt(logDate).then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    let message: any = rslt.err_msg;
                    this.okPayroll = false;
                    this.comCtrl.presentOnebuttonAlert("Warning!", message);
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    onSelectDate(touched?: boolean) {
        let dit = new Date(this.startDate);
        let cd = formatDate(dit, 'yyyy-MM-dd', 'en-US');
        let plusone = dit.setDate( dit.getDate() + 1 );
        this.maxDate = formatDate(plusone, 'yyyy-MM-dd', 'en-US');
        this.minDate = cd;

        console.log(this.startDate);
        console.log(cd);
        console.log(this.maxDate);
        console.log(this.minDate);

        let logs: any = this.getLogs();
        
        if (logs) {
            if (!logs.endDate) {
                this.ifPayrollLock();
                return false;
            }
            if (!logs.startDate) {
                return false;
            }
        } else {
            return false;
        }
        
        this.isPristine = true;
        this.hoursProv.calculateOvertimeRequest(logs).then(res=>{
            if (res) {
                let rslt: any = res;
                if (!rslt.error) {
                    this.totalHrs = rslt.total_hours;
                }
            }
            this.isPristine = false;
        }).catch(err=>{
            console.log(err);
            this.isPristine = false;
        });
    }

    getLogs() {
        let dit: any = '';
        let dit2: any = '';
        let formdit: any = '';
        let formdit2: any = '';
        if (this.startDate) {
          dit = new Date(this.startDate);
          formdit = formatDate(dit, 'yyyy-MM-dd HH:mm', 'en-US');
        }
        if (this.endDate) {
          dit2 = new Date(this.endDate);
          formdit2 = formatDate(dit2, 'yyyy-MM-dd HH:mm', 'en-US');
        }
        let asd = {
            'startDate' : formdit,
            'endDate' : formdit2,
            'reason': this.reason
        };
        console.log(asd);
        return asd;
    }

    gotoBack() {
        this.router.navigate(['/members/overtime']);
    }

}
