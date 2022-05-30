import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { HoursProvider } from 'src/app/services/hours/hours';
import { formatDate } from '@angular/common';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { ToastController, AlertController, NavController, LoadingController } from '@ionic/angular';

@Component({
  selector: 'app-change-logs',
  templateUrl: './change-logs.page.html',
  styleUrls: ['./change-logs.page.scss'],
})
export class ChangeLogsPage implements OnInit {
    currentDate: string;
    reason: any = '';
    logDetails: any = "";
    isHalfDay: boolean = false;
    timeIn: any;
    lunchOut: any;
    lunchIn: any;
    firstBreakIn: any;
    firstBreakOut: any;
    secondBreakOut: any;
    secondBreakIn: any;
    timeOut: any;
    totalHrs: any;
    empTimeinId: any;
    checkBreak: any;
    hasBreakOne: boolean = false;
    hasBreakTwo: boolean = false;
    hasLunch: boolean = false;
    isPristine: boolean = true;
    halfDay: boolean = false;
    nullDate: any = false;
    isSplit: boolean = false;
    islocked: boolean = false;
    minDate: any;
    maxDate: any;
    modalTitle: string = "Change Logs";

    constructor(
      private route: ActivatedRoute, 
      private router: Router,
      private hoursProv: HoursProvider,
      public comCtrl: MyCommonServices,
      public toastCtrl: ToastController,
      public alertController: AlertController,
      public nav: NavController,
      public loadingCtrl: LoadingController,
    ) { 
        this.hasBreakOne = false;
        this.hasBreakTwo = false;

        this.route.queryParams.subscribe(params => {
            if (this.router.getCurrentNavigation().extras.state) {
            this.logDetails = this.router.getCurrentNavigation().extras.state.details;
            }
        });

    }

    ngOnInit() {
        let cd: any;
        if (this.logDetails) {
            let datePart = this.logDetails.date;
            // datePart = datePart.split('-');
            cd = new Date(datePart);
            console.log(datePart);
            if (this.logDetails.rest_day_r_a == "yes") {
                this.modalTitle = "Apply Rest Day";
            } else if (this.logDetails.holiday_approve == "yes") {
                this.modalTitle = "Apply Holiday";
            }
        } else {
            cd = new Date();
        }
        console.log(cd);
        this.currentDate = formatDate(cd, 'yyyy-MM-dd', 'en-US');
        this.nullDate = this.currentDate;
        this.totalHrs = 0;

        this.empTimeinId = this.logDetails.employee_time_in_id;
        if (this.currentDate) {
            let now = new Date(this.currentDate);
            var plusday = now.setDate(cd.getDate() + 1);
            var minusday = now.setDate(cd.getDate() - 1);
            this.maxDate = formatDate(plusday, 'yyyy-MM-dd', 'en-US');
            this.minDate = formatDate(minusday, 'yyyy-MM-dd', 'en-US');

            this.onChangeDate();
        }
    }

    gotoBack() {
        this.router.navigate(['/members/timesheets']);
    }

    async presentLoading() {
        const loading = await this.loadingCtrl.create({
          message: 'Please wait...',
        });
        await loading.present();
    
        const { role, data } = await loading.onDidDismiss();
    
        console.log('Loading dismissed!');
    }

    onSubmit() {
        if (this.islocked) {
            this.comCtrl.presentOnebuttonAlert("Locked", "Filing of attendance adjustment is currently suspended by your admin. You may try again at a different time.");
            return false;
        }
        let asd = this.getLogs();

        let errs = this.hoursProv.logsValidation(asd);
        if (errs) {
            this.comCtrl.presentLogsError(errs);
            return false;
        }

        this.presentLoading();

        this.hoursProv.changeLogs(this.empTimeinId, asd).then(res=>{
            let rslt: any = res;
            if (res) {
                if (rslt.error) {
                    this.comCtrl.presentToast(rslt.error_msg, "error");
                } else {
                    this.comCtrl.presentToast('Your request has been submitted.', 'success');
                    this.nav.navigateBack('/members/timesheets');
                }
                this.loadingCtrl.dismiss();
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    async presentToaster(msg: any, second: any) {
      const toast = await this.toastCtrl.create({
        message: msg,
        duration: 6000
      });
      toast.present();
      toast.onDidDismiss().then(res=>{
        this.comCtrl.presentToast(second, 'error', 6000);
      }).catch(err=>{
        this.isPristine = true;
        console.log(err);
    });
    }

    onSelectDate() {
        console.log("start computing");
        let logs: any = this.getLogs();            
        if (! logs) {
            return false;
        }

        if (!logs.timeOut || !logs.timeIn) {
            return false;
        }

        console.log(logs);
        
        this.isPristine = false;
        this.totalHrs = 0;
        
        this.hoursProv.checkTotalHours(logs).then(res=>{
            
            if (res) {
                let rslt: any = res;
                if (!rslt.error) {
                    this.totalHrs = rslt.total_hours;
                }

                if (rslt.ecreason) {
                    // let err1 = this.comCtrl.presentToast(rslt.ecreason, "error", 6000);
                    this.comCtrl.presentToast(rslt.ecreason, 'error', 6000);
                    if (rslt.etime_out_date) {
                        
                        // err1.onDidDismiss(() => {
                        //     this.comCtrl.presentToast(rslt.etime_out_date, "error", 6000);
                        // });
                    }
                } else {
                    if (rslt.etime_out_date) {
                        this.comCtrl.presentToast(rslt.etime_out_date, "error", 6000);
                    }
                }
            }
            this.isPristine = true;
        }).catch(err=>{
            this.isPristine = true;
            console.log(err);
        });
    }

    payrollLockChecker() {
        if (!this.currentDate) {
            return false;
        }
        let d = new Date(this.currentDate);
        let logDate = formatDate(d, 'yyyy-MM-dd', 'en-US');
        this.hoursProv.checkPayrollLock(logDate).then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    let message: any = rslt.err_msg;
                    this.comCtrl.presentOnebuttonAlert("Warning!", message);
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    onChangeDate() {
        this.onSelectDate();
        let now = new Date(this.currentDate);
        this.hoursProv.breakChecker(formatDate(now, 'yyyy-MM-dd', 'en-US')).then(res=>{
            
            if (res) {
                let rslt: any = res;
                if (!rslt.error) {
                    this.hasBreakOne = (rslt.break_1) ? true : false;
                    this.hasBreakTwo  = (rslt.break_2) ? true : false;
                    this.hasLunch  = (rslt.break_in_min) ? true : false;
                } else {
                    this.alertDismissPage('Locked!', rslt.admin_lock);
                }
            }            
        }).catch(err=>{
            console.log(err);
        });

        this.payrollLockChecker();
    }

    async alertDismissPage(title: any, msg: any) {
        const alert = await this.alertController.create({
            header: 'Locked!',
            message: msg,
            buttons: [
                {
                    text: 'Got it!',
                    role: 'cancel',
                    cssClass: 'secondary',
                    handler: (e) => {
                        this.nav.navigateBack('/members/timesheets');
                    }
                }
            ]
        });
    
        await alert.present();
    }

    getLogs() {

        var schedDate = (!this.currentDate) ? '' : new Date(this.currentDate);
        var timeInDate = (!this.timeIn) ? '' :  new Date(this.timeIn);
        var timeIn = (!this.timeIn) ? '' :  new Date(this.timeIn);
        var timeOut = (!this.timeOut) ? '' :  new Date(this.timeOut);
        var lunchIn = (!this.lunchIn) ? '' :  new Date(this.lunchIn);
        var lunchOut = (!this.lunchOut) ? '' :  new Date(this.lunchOut);
        var firstBreakIn = (!this.firstBreakIn) ? '' :  new Date(this.firstBreakIn);
        var firstBreakOut = (!this.firstBreakOut) ? '' :  new Date(this.firstBreakOut);
        var secondBreakIn = (!this.secondBreakIn) ? '' :  new Date(this.secondBreakIn);
        var secondBreakOut = (!this.secondBreakOut) ? '' :  new Date(this.secondBreakOut);
    
        let asd = {
            'schedDate' : (!this.currentDate) ? '' : formatDate(schedDate, 'yyyy-MM-dd', 'en-US'),
            'timeInDate' : (!this.timeIn) ? '' : formatDate(timeInDate, 'yyyy-MM-dd', 'en-US'),
            'timeIn': (!this.timeIn) ? '' : formatDate(timeIn, 'yyyy-MM-dd HH:mm', 'en-US'),
            'timeOut': (!this.timeOut) ? '' : formatDate(timeOut, 'yyyy-MM-dd HH:mm', 'en-US'),
            'lunchIn': (!this.lunchIn) ? '' : formatDate(lunchIn, 'yyyy-MM-dd HH:mm', 'en-US'),
            'lunchOut': (!this.lunchOut) ? '' : formatDate(lunchOut, 'yyyy-MM-dd HH:mm', 'en-US'),
            'firstBreakIn': (!this.firstBreakIn) ? '' : formatDate(firstBreakIn, 'yyyy-MM-dd HH:mm', 'en-US'),
            'firstBreakOut': (!this.firstBreakOut) ? '' : formatDate(firstBreakOut, 'yyyy-MM-dd HH:mm', 'en-US'),
            'secondBreakIn': (!this.secondBreakIn) ? '' : formatDate(secondBreakIn, 'yyyy-MM-dd HH:mm', 'en-US'),
            'secondBreakOut': (!this.secondBreakOut) ? '' : formatDate(secondBreakOut, 'yyyy-MM-dd HH:mm', 'en-US'),
            'halfday': this.halfDay,
            'reason': this.reason,
            'empTimeinId' : this.empTimeinId
        };
        
        return asd;
    }

}
