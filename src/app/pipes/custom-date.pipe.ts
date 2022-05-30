import { Pipe, PipeTransform } from '@angular/core';
import * as moment from 'moment';

@Pipe({
  name: 'customDate'
})
export class CustomDatePipe implements PipeTransform {

  transform(value: string) {
    return moment(value, "YYYY-MM-DD").format("DD-MMM-YY");
  }

}
