import { Component, OnInit, ViewChild } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { LeavesProvider } from 'src/app/services/leaves/leaves';
import { IonSlides, AlertController, NavController } from '@ionic/angular';
import { Router, NavigationExtras } from '@angular/router';
import { MyCommonServices } from 'src/app/shared/myCommonServices';

@Component({
  selector: 'app-leaves',
  templateUrl: './leaves.page.html',
  styleUrls: ['./leaves.page.scss'],
})
export class LeavesPage implements OnInit {

    @ViewChild(IonSlides, {static:false}) slides: IonSlides;

    slideOpts = {
        initialSlide: 0,
        speed: 400
    };

    fetchingApprove: boolean;
    fetchingBalance: boolean;
    totalPages: any;
    currentPage: any;
    
    selectedSegment: string;
    leaveBalances: any;
    sssSegment: any;
    fakeUsers: Array<any>;

    approvedPage: any;
    approveList: any;
    approveTotal: any;

    pendingPage: any;
    pendingList: any;
    pendingTotal: any;
    fetchingPending: boolean;

    rejectPage: any;
    rejectList: any;
    rejectTotal: any;
    fetchingReject: boolean;

    constructor(
        private mainApp : AppComponent,
        public leaveProv: LeavesProvider,
        private router: Router,
        public alertCtrl: AlertController,
        public nav: NavController,
        public comCtrl: MyCommonServices,
    ) {
        this.fakeUsers = new Array(10);
        // check session
        this.mainApp.apiSessionChecker();

        this.selectedSegment = 'balance';
        this.sssSegment = 'approved';
        
        this.currentPage = "1";
        this.approvedPage = "1";
        this.fetchingApprove = true;
        this.fetchingBalance = true;
        this.fetchingPending = true;
        this.fetchingReject = true;
    }

    ngOnInit() {
        this.getLeaveBalance();
        this.getApprovedHistory();
        this.getPendingHistory();
        this.getRejectHistory();
    }

    applyThisLeave(data: any) {
        
        var req_doc = data.required_documents;
        if (req_doc) {
            this.attachmentAlert(req_doc);
        } else {
            if (data.remaining_leave_credits > 0) {
                let navigationExtras: NavigationExtras = {
                    state: {
                        details: data
                    }
                };
                this.router.navigate(['/members/apply'], navigationExtras);
            } else {
                this.comCtrl.presentToast("No leave credits left.", "error");
            }
        }
        
    }

    async attachmentAlert(req_doc: any) {
        const alert = await this.alertCtrl.create({
            header: 'File Attachment Required',
            message: "Applying for this leave type requires "+req_doc +'. Mobile app is unable cater this functionality yet. Please use the browser instead.',
            buttons: [
                {
                    text: 'Got it!',
                    role: 'cancel',
                }
            ]
        });
        await alert.present();
      }

    onSegmentChanged(event: any) {
        if (this.selectedSegment == 'balance') {
            this.slides.slideTo(0);
        } else {
            this.slides.slideTo(1);
        }
        
    }

    onSlideChanged(event: any) {
        this.slides.getActiveIndex().then(index => {
            console.log(index);
            console.log('currentIndex:', index);
            if (index == 0) {
                this.selectedSegment = "balance";
            } else {
                this.selectedSegment = "history";
            }
        });
    }

    doInfiniteApprove(infiniteScroll: any) {

        this.approvedPage = this.approvedPage+1;
        this.selectedSegment = 'history';
        setTimeout(() => {
            this.leaveProv.getLeaveHistory(this.approvedPage+'', 'approve').then(res=>{
                if (res) {
                    let rslt: any = res;
                    console.log(rslt);
                    if (rslt) {
                        for(let i=0; i<rslt.list.length; i++) {
                            this.approveList.push(rslt.list[i]);
                        }
                    }
                    
                }
                infiniteScroll.target.complete();
                console.log(this.approveList);
            }).catch(err=>{
                infiniteScroll.target.complete();
                console.log(err);
            });

            if (this.approvedPage == this.approveTotal) {
                infiniteScroll.target.complete();
            }
        }, 500);
    }

    getApprovedHistory() {
        this.leaveProv.getLeaveHistory(this.approvedPage, 'approve').then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.approveList = rslt.list;
                    this.approvedPage = parseInt(rslt.page, 10);
                    this.approveTotal = parseInt(rslt.total, 10);
                    this.fetchingApprove = false;
                }
                
            }
        }).catch(err=>{
            console.log(err);
            this.fetchingApprove = false;
        });
    }

    getPendingHistory() {
        this.leaveProv.getLeaveHistory(this.approvedPage, 'pending').then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.pendingList = rslt.list;
                    this.pendingPage = parseInt(rslt.page, 10);
                    this.pendingTotal = parseInt(rslt.total, 10);
                    this.fetchingPending = false;
                }
                
            }
        }).catch(err=>{
            console.log(err);
            this.fetchingApprove = false;
        });
    }

    getRejectHistory() {
        this.leaveProv.getLeaveHistory(this.approvedPage, 'reject').then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.rejectList = rslt.list;
                    this.rejectPage = parseInt(rslt.page, 10);
                    this.rejectTotal = parseInt(rslt.total, 10);
                    this.fetchingReject = false;
                }
                
            }
        }).catch(err=>{
            console.log(err);
            this.fetchingApprove = false;
        });
    }

    getLeaveBalance() {
        this.leaveProv.getBalance().then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt) {
                    this.leaveBalances = rslt;
                }
                
            }
            this.fetchingBalance = false;
        }).catch(err=>{
            console.log(err);
            this.fetchingBalance = false;
        });
    }

    doRefresh(event: any) {
        this.leaveBalances = "";
        this.fetchingBalance = true;

        setTimeout(() => {
            this.leaveProv.getBalance().then(res=>{
                if (res) {
                    let rslt: any = res;
                    
                    this.leaveBalances = rslt;
                    console.log(this.leaveBalances);
                }
                this.fetchingBalance = false;
                event.target.complete();
            }).catch(err=>{
                console.log(err);
                this.fetchingBalance = false;
                event.target.complete();
            });
        
        }, 2000);
    }

}
