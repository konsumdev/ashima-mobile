import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { HoursProvider } from 'src/app/services/hours/hours';

@Component({
  selector: 'app-overtime-details',
  templateUrl: './overtime-details.page.html',
  styleUrls: ['./overtime-details.page.scss'],
})
export class OvertimeDetailsPage implements OnInit {

  passedDetails: any;
  otId: any;
  approvers: any;

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
    private hoursProv: HoursProvider,
  ) {
    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.passedDetails = this.router.getCurrentNavigation().extras.state.details;
      }
    });
  }

  ngOnInit() {
    if (this.passedDetails) {
      this.otId = this.passedDetails.overtime_id;
      this.getApprovers();
    }
  }

  getApprovers() {
    this.hoursProv.getOvertimeApprovers(this.otId).then(res=>{
        let rslt = res;
        if (rslt) {
            this.approvers = rslt;
        }
    }).catch(err=>{
        console.log(err);
    });
  }

  gotoBack() {
        this.router.navigate(['/members/overtime']);
    }

}
