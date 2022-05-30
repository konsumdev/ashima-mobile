import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, NavigationExtras } from '@angular/router';

@Component({
  selector: 'app-compensation',
  templateUrl: './compensation.page.html',
  styleUrls: ['./compensation.page.scss'],
})
export class CompensationPage implements OnInit {
  pDetails: any;
  compDet: any;
  compHis: any;

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
  ) { 
    this.pDetails = 'basicInfo';

    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.compDet = this.router.getCurrentNavigation().extras.state.compDetails;
        this.compHis = this.router.getCurrentNavigation().extras.state.comHistory;
      }
    });
  }

  ngOnInit() {
  }

  gotoHistory(data: any) {
    let navigationExtras: NavigationExtras = {
      state: {
        comHstryDet: data
      }
    };
    this.router.navigate(['/members/compensation-history'], navigationExtras);
  }

}
